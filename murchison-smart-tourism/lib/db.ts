import 'server-only';
import { createClient, type Client, type InValue } from '@libsql/client';
import bcrypt from 'bcryptjs';

/**
 * Database: a local SQLite file on your computer (no installation needed), or a Turso database on Vercel.
 * On Vercel set TURSO_DATABASE_URL and TURSO_AUTH_TOKEN. Tables are created automatically on first use.
 */
const url = process.env.TURSO_DATABASE_URL || 'file:murchison.db';
const authToken = process.env.TURSO_AUTH_TOKEN;

const g = globalThis as unknown as { __mfDb?: Client; __mfReady?: Promise<void> };
export const db: Client = g.__mfDb ?? (g.__mfDb = createClient({ url, authToken }));

export const isLocalDb = url.startsWith('file:');
export const isVercel = !!process.env.VERCEL;

const SCHEMA = [
  `CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin','tourist','operator')),
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS operators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    company TEXT NOT NULL,
    licence_no TEXT,
    phone TEXT,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    review_note TEXT,
    reviewed_by INTEGER REFERENCES users(id),
    reviewed_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    operator_id INTEGER NOT NULL REFERENCES operators(id) ON DELETE CASCADE,
    title TEXT NOT NULL,
    days INTEGER NOT NULL,
    price_ugx INTEGER NOT NULL,
    activities TEXT NOT NULL DEFAULT '',
    description TEXT,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reference TEXT NOT NULL UNIQUE,
    tourist_id INTEGER NOT NULL REFERENCES users(id),
    operator_id INTEGER NOT NULL REFERENCES operators(id),
    package_id INTEGER REFERENCES packages(id),
    plan TEXT,
    start_date TEXT NOT NULL,
    days INTEGER NOT NULL,
    adults INTEGER NOT NULL,
    children INTEGER NOT NULL DEFAULT 0,
    message TEXT,
    status TEXT NOT NULL DEFAULT 'enquiry' CHECK (status IN ('enquiry','quoted','paid','confirmed','completed','cancelled','declined')),
    quote_ugx INTEGER,
    quote_note TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL REFERENCES bookings(id),
    method TEXT NOT NULL CHECK (method IN ('mtn','airtel','card')),
    phone TEXT,
    amount_ugx INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','successful','failed')),
    provider TEXT NOT NULL,
    tx_ref TEXT NOT NULL UNIQUE,
    provider_ref TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS sms_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    phone TEXT NOT NULL,
    message TEXT NOT NULL,
    status TEXT NOT NULL,
    provider TEXT NOT NULL,
    detail TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS login_activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    email TEXT NOT NULL,
    event TEXT NOT NULL CHECK (event IN ('login','failed','logout','register')),
    ip TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS scenes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    activity_id TEXT,
    description TEXT,
    image_url TEXT NOT NULL,
    sort INTEGER NOT NULL DEFAULT 0,
    published INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
  )`,
  `CREATE TABLE IF NOT EXISTS hotspots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scene_id INTEGER NOT NULL REFERENCES scenes(id) ON DELETE CASCADE,
    pitch REAL NOT NULL,
    yaw REAL NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('info','scene')),
    text TEXT NOT NULL,
    target_scene_id INTEGER REFERENCES scenes(id) ON DELETE SET NULL
  )`,
  'CREATE INDEX IF NOT EXISTS idx_bookings_tourist ON bookings(tourist_id)',
  'CREATE INDEX IF NOT EXISTS idx_bookings_operator ON bookings(operator_id)',
  'CREATE INDEX IF NOT EXISTS idx_login_created ON login_activities(created_at)',
];

/**
 * The first administrator is created from environment variables on first run:
 * ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME and ADMIN_PHONE (see .env.example).
 */
export function defaultAdmin() {
  return {
    name: process.env.ADMIN_NAME || 'System Administrator',
    email: (process.env.ADMIN_EMAIL || 'admin@murchison.local').toLowerCase(),
    phone: process.env.ADMIN_PHONE || null,
    password: process.env.ADMIN_PASSWORD || 'Murchison@2026',
  };
}

async function init() {
  await db.batch(SCHEMA, 'write');
  await db.execute('PRAGMA foreign_keys = ON').catch(() => {});
  const admins = await db.execute("SELECT COUNT(*) AS n FROM users WHERE role = 'admin'");
  if (Number(admins.rows[0].n) === 0) {
    const a = defaultAdmin();
    await db.execute({
      sql: "INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'admin')",
      args: [a.name, a.email, a.phone, await bcrypt.hash(a.password, 10)],
    });
  }
}

/** Makes sure the tables exist (runs once per server start). */
export function ready(): Promise<void> {
  if (!g.__mfReady) g.__mfReady = init().catch(e => { g.__mfReady = undefined; throw e; });
  return g.__mfReady;
}

export type Row = Record<string, unknown>;

export async function all<T = Row>(sql: string, args: InValue[] = []): Promise<T[]> {
  await ready();
  const r = await db.execute({ sql, args });
  return r.rows.map(row => ({ ...row })) as T[];
}
export async function one<T = Row>(sql: string, args: InValue[] = []): Promise<T | null> {
  const rows = await all<T>(sql, args);
  return rows[0] ?? null;
}
export async function run(sql: string, args: InValue[] = []) {
  await ready();
  const r = await db.execute({ sql, args });
  return { changes: r.rowsAffected, id: r.lastInsertRowid != null ? Number(r.lastInsertRowid) : 0 };
}

// ---------- Types ----------
export type Role = 'admin' | 'tourist' | 'operator';
export type User = { id: number; name: string; email: string; phone: string | null; role: Role; is_active: number; created_at: string };
export type Operator = { id: number; user_id: number; company: string; licence_no: string | null; phone: string | null; description: string | null; status: 'pending' | 'approved' | 'rejected'; review_note: string | null; created_at: string };
export type Package = { id: number; operator_id: number; title: string; days: number; price_ugx: number; activities: string; description: string | null; is_active: number };
export type BookingStatus = 'enquiry' | 'quoted' | 'paid' | 'confirmed' | 'completed' | 'cancelled' | 'declined';
export type Booking = {
  id: number; reference: string; tourist_id: number; operator_id: number; package_id: number | null; plan: string | null; start_date: string; days: number;
  adults: number; children: number; message: string | null; status: BookingStatus; quote_ugx: number | null; quote_note: string | null; created_at: string; updated_at: string;
};
export type Payment = { id: number; booking_id: number; method: 'mtn' | 'airtel' | 'card'; phone: string | null; amount_ugx: number; status: 'pending' | 'successful' | 'failed'; provider: string; tx_ref: string; provider_ref: string | null; created_at: string };
export type Scene = { id: number; title: string; activity_id: string | null; description: string | null; image_url: string; sort: number; published: number };
export type Hotspot = { id: number; scene_id: number; pitch: number; yaw: number; type: 'info' | 'scene'; text: string; target_scene_id: number | null };
