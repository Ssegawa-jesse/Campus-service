'use server';
import bcrypt from 'bcryptjs';
import { redirect } from 'next/navigation';
import { revalidatePath } from 'next/cache';
import { one, run, type Operator } from '@/lib/db';
import { requireUser } from '@/lib/auth';
import { normalizePhone, sendSms } from '@/lib/sms';
import { ACTIVITIES } from '@/lib/data';
import type { FormState } from './auth';

const str = (fd: FormData, k: string, max = 200) => String(fd.get(k) ?? '').trim().slice(0, max);
const int = (fd: FormData, k: string) => Math.trunc(Number(fd.get(k) ?? 0));
const admin = () => requireUser(['admin']);

// ---------------- Operators and users ----------------

export async function reviewOperatorAction(fd: FormData) {
  const me = await admin();
  const op = await one<Operator & { user_phone: string | null }>('SELECT o.*, u.phone AS user_phone FROM operators o JOIN users u ON u.id = o.user_id WHERE o.id = ?', [int(fd, 'id')]);
  if (!op) return;
  const approve = str(fd, 'decision') === 'approve';
  const note = str(fd, 'note', 500) || null;
  await run("UPDATE operators SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = datetime('now') WHERE id = ?", [approve ? 'approved' : 'rejected', note, me.id, op.id]);
  await sendSms(op.phone || op.user_phone, approve
    ? `Congratulations! ${op.company} has been approved on the Murchison Falls Smart Tourism Platform. Log in to add your packages and receive bookings.`
    : `Your operator registration for ${op.company} was not approved.${note ? ' Reason: ' + note : ''}`);
  revalidatePath('/admin'); revalidatePath('/admin/operators');
}

export async function toggleUserAction(fd: FormData) {
  const me = await admin();
  const id = int(fd, 'id');
  if (id === me.id) return; // administrators cannot lock themselves out
  await run('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [id]);
  revalidatePath('/admin/users');
}

export async function createAdminAction(_: FormState, fd: FormData): Promise<FormState> {
  await admin();
  const name = str(fd, 'name', 100);
  const email = str(fd, 'email').toLowerCase();
  const phone = normalizePhone(str(fd, 'phone', 30));
  const password = String(fd.get('password') ?? '');
  if (name.length < 2) return { error: 'Enter the full name.' };
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return { error: 'Enter a valid email address.' };
  if (password.length < 8) return { error: 'The password must have at least 8 characters.' };
  if (await one('SELECT id FROM users WHERE email = ?', [email])) return { error: 'An account with this email already exists.' };
  await run("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'admin')", [name, email, phone, await bcrypt.hash(password, 10)]);
  revalidatePath('/admin/users');
  return { ok: `Administrator ${name} added. Share the password with them privately and ask them to change it.` };
}

export async function testSmsAction(_: FormState, fd: FormData): Promise<FormState> {
  await admin();
  const phone = normalizePhone(str(fd, 'phone', 30));
  const message = str(fd, 'message', 300);
  if (!phone) return { error: 'Enter a valid phone number.' };
  if (!message) return { error: 'Enter a message.' };
  await sendSms(phone, message);
  revalidatePath('/admin/sms');
  return { ok: 'Message processed — see the log below.' };
}

// ---------------- 360° scenes and hotspots ----------------

const validActivity = (a: string) => (ACTIVITIES.some(x => x.id === a) ? a : null);
const validImage = (u: string) => /^(\/api\/uploads\/[a-z0-9-]+\.(jpg|png|webp)|https:\/\/[a-z0-9.-]+\.public\.blob\.vercel-storage\.com\/\S+)$/i.test(u);

export async function createSceneAction(_: FormState, fd: FormData): Promise<FormState> {
  await admin();
  const title = str(fd, 'title', 120);
  const image = str(fd, 'image_url', 500);
  if (title.length < 2) return { error: 'Enter a title for the scene.' };
  if (!validImage(image)) return { error: 'Upload the 360° photo first.' };
  const sort = Number((await one<{ m: number }>('SELECT COALESCE(MAX(sort), 0) AS m FROM scenes'))?.m ?? 0) + 1;
  const { id } = await run('INSERT INTO scenes (title, activity_id, description, image_url, sort) VALUES (?, ?, ?, ?, ?)',
    [title, validActivity(str(fd, 'activity_id', 40)), str(fd, 'description', 1000) || null, image, sort]);
  redirect(`/admin/scenes/${id}`);
}

export async function updateSceneAction(_: FormState, fd: FormData): Promise<FormState> {
  await admin();
  const id = int(fd, 'id');
  const title = str(fd, 'title', 120);
  if (title.length < 2) return { error: 'Enter a title for the scene.' };
  const image = str(fd, 'image_url', 500);
  if (image && !validImage(image)) return { error: 'The new image is not valid.' };
  await run('UPDATE scenes SET title = ?, activity_id = ?, description = ?, sort = ?, image_url = COALESCE(?, image_url) WHERE id = ?',
    [title, validActivity(str(fd, 'activity_id', 40)), str(fd, 'description', 1000) || null, int(fd, 'sort'), image || null, id]);
  revalidatePath(`/admin/scenes/${id}`); revalidatePath('/explore');
  return { ok: 'Scene saved.' };
}

export async function togglePublishAction(fd: FormData) {
  await admin();
  const id = int(fd, 'id');
  await run('UPDATE scenes SET published = 1 - published WHERE id = ?', [id]);
  revalidatePath(`/admin/scenes/${id}`); revalidatePath('/admin/scenes'); revalidatePath('/explore');
}

export async function deleteSceneAction(fd: FormData) {
  await admin();
  const id = int(fd, 'id');
  await run('UPDATE hotspots SET target_scene_id = NULL WHERE target_scene_id = ?', [id]);
  await run('DELETE FROM hotspots WHERE scene_id = ?', [id]);
  await run('DELETE FROM scenes WHERE id = ?', [id]);
  revalidatePath('/explore');
  redirect('/admin/scenes');
}

export async function addHotspotAction(_: FormState, fd: FormData): Promise<FormState> {
  await admin();
  const sceneId = int(fd, 'scene_id');
  const pitch = Number(fd.get('pitch')); const yaw = Number(fd.get('yaw'));
  const type = str(fd, 'type') === 'scene' ? 'scene' : 'info';
  const text = str(fd, 'text', 500);
  const target = type === 'scene' ? int(fd, 'target_scene_id') || null : null;
  if (!Number.isFinite(pitch) || !Number.isFinite(yaw) || Math.abs(pitch) > 90 || Math.abs(yaw) > 360) return { error: 'Click inside the photo to choose where the hotspot goes.' };
  if (!text) return { error: 'Enter the hotspot text.' };
  if (type === 'scene' && (!target || target === sceneId || !(await one('SELECT id FROM scenes WHERE id = ?', [target])))) return { error: 'Choose the scene this arrow leads to.' };
  await run('INSERT INTO hotspots (scene_id, pitch, yaw, type, text, target_scene_id) VALUES (?, ?, ?, ?, ?, ?)',
    [sceneId, Math.round(pitch * 10) / 10, Math.round(yaw * 10) / 10, type, text, target]);
  revalidatePath(`/admin/scenes/${sceneId}`); revalidatePath('/explore');
  return { ok: 'Hotspot added.' };
}

export async function deleteHotspotAction(fd: FormData) {
  await admin();
  const h = await one<{ scene_id: number }>('SELECT scene_id FROM hotspots WHERE id = ?', [int(fd, 'id')]);
  if (!h) return;
  await run('DELETE FROM hotspots WHERE id = ?', [int(fd, 'id')]);
  revalidatePath(`/admin/scenes/${h.scene_id}`); revalidatePath('/explore');
}
