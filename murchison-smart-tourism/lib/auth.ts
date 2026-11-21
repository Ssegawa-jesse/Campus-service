import 'server-only';
import { cookies, headers } from 'next/headers';
import { redirect } from 'next/navigation';
import { jwtVerify, SignJWT } from 'jose';
import { one, run, type Operator, type Role, type User } from './db';

const COOKIE = 'mf_session';
const MAX_AGE = 60 * 60 * 24 * 7; // 7 days

function secret() {
  const s = process.env.SESSION_SECRET;
  if (!s) {
    if (process.env.VERCEL) throw new Error('SESSION_SECRET is not set. Add it under Vercel → Project → Settings → Environment Variables.');
    return new TextEncoder().encode('local-development-secret-change-me-in-production');
  }
  return new TextEncoder().encode(s);
}

export async function createSession(user: { id: number; role: Role }) {
  const token = await new SignJWT({ role: user.role }).setProtectedHeader({ alg: 'HS256' })
    .setSubject(String(user.id)).setIssuedAt().setExpirationTime(`${MAX_AGE}s`).sign(secret());
  (await cookies()).set(COOKIE, token, { httpOnly: true, sameSite: 'lax', secure: process.env.NODE_ENV === 'production', path: '/', maxAge: MAX_AGE });
}

export async function destroySession() {
  (await cookies()).delete(COOKIE);
}

/** The logged-in user, or null. */
export async function currentUser(): Promise<User | null> {
  const token = (await cookies()).get(COOKIE)?.value;
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, secret());
    const u = await one<User>('SELECT id, name, email, phone, role, is_active, created_at FROM users WHERE id = ?', [Number(payload.sub)]);
    return u && u.is_active ? u : null;
  } catch {
    return null;
  }
}

/** Use at the top of protected pages and actions. Sends visitors to the login page or to their own dashboard. */
export async function requireUser(roles?: Role[]): Promise<User> {
  const u = await currentUser();
  if (!u) redirect('/login');
  if (roles && !roles.includes(u.role)) redirect(dashboardFor(u.role));
  return u;
}

export async function operatorFor(userId: number) {
  return one<Operator>('SELECT * FROM operators WHERE user_id = ?', [userId]);
}

export function dashboardFor(role: Role) {
  return role === 'admin' ? '/admin' : role === 'operator' ? '/operator' : '/tourist';
}

/** Records registrations, logins, failed logins and logouts for the administrators. */
export async function recordLogin(event: 'login' | 'failed' | 'logout' | 'register', email: string, userId: number | null) {
  const h = await headers();
  const ip = (h.get('x-forwarded-for') || '').split(',')[0].trim() || h.get('x-real-ip') || null;
  await run('INSERT INTO login_activities (user_id, email, event, ip, user_agent) VALUES (?, ?, ?, ?, ?)',
    [userId, email.toLowerCase(), event, ip, (h.get('user-agent') || '').slice(0, 250)]);
}

/** Five failed logins in 15 minutes locks the account for 15 minutes. */
export async function tooManyFailures(email: string) {
  const r = await one<{ n: number }>("SELECT COUNT(*) AS n FROM login_activities WHERE email = ? AND event = 'failed' AND created_at > datetime('now', '-15 minutes')", [email.toLowerCase()]);
  return Number(r?.n ?? 0) >= 5;
}
