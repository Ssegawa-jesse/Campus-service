'use server';
import bcrypt from 'bcryptjs';
import { redirect } from 'next/navigation';
import { one, run } from '@/lib/db';
import { createSession, currentUser, dashboardFor, destroySession, recordLogin, requireUser, tooManyFailures } from '@/lib/auth';
import { normalizePhone, sendSms, smsAdmins } from '@/lib/sms';

export type FormState = { error?: string; ok?: string };

const str = (fd: FormData, k: string, max = 200) => String(fd.get(k) ?? '').trim().slice(0, max);
const safeNext = (n: string) => (n.startsWith('/') && !n.startsWith('//') ? n : '');

export async function loginAction(_: FormState, fd: FormData): Promise<FormState> {
  const email = str(fd, 'email').toLowerCase();
  const password = String(fd.get('password') ?? '');
  if (!email || !password) return { error: 'Enter your email and password.' };
  if (await tooManyFailures(email)) return { error: 'Too many failed attempts. Wait 15 minutes and try again.' };
  const u = await one<{ id: number; role: 'admin' | 'tourist' | 'operator'; password_hash: string; is_active: number }>(
    'SELECT id, role, password_hash, is_active FROM users WHERE email = ?', [email]);
  if (!u || !(await bcrypt.compare(password, u.password_hash))) {
    await recordLogin('failed', email, u?.id ?? null);
    return { error: 'The email or password is not correct.' };
  }
  if (!u.is_active) return { error: 'This account has been deactivated. Contact the administrator.' };
  await createSession(u);
  await recordLogin('login', email, u.id);
  redirect(safeNext(str(fd, 'next')) || dashboardFor(u.role));
}

export async function registerAction(_: FormState, fd: FormData): Promise<FormState> {
  const role = str(fd, 'role') === 'operator' ? 'operator' : 'tourist';
  const name = str(fd, 'name', 100);
  const email = str(fd, 'email').toLowerCase();
  const phone = normalizePhone(str(fd, 'phone', 30));
  const password = String(fd.get('password') ?? '');
  const confirm = String(fd.get('confirm') ?? '');
  const company = str(fd, 'company', 120);
  if (name.length < 2) return { error: 'Enter your full name.' };
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return { error: 'Enter a valid email address.' };
  if (!phone) return { error: 'Enter a valid phone number, for example 0772 123 456. It is used for SMS updates.' };
  if (password.length < 8) return { error: 'The password must have at least 8 characters.' };
  if (password !== confirm) return { error: 'The two passwords do not match.' };
  if (role === 'operator' && company.length < 2) return { error: 'Enter your company name.' };
  if (await one('SELECT id FROM users WHERE email = ?', [email])) return { error: 'An account with this email already exists. Log in instead.' };

  const { id } = await run('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)',
    [name, email, phone, await bcrypt.hash(password, 10), role]);
  if (role === 'operator') {
    await run('INSERT INTO operators (user_id, company, licence_no, phone, description) VALUES (?, ?, ?, ?, ?)',
      [id, company, str(fd, 'licence', 60) || null, phone, str(fd, 'description', 1000) || null]);
    await smsAdmins(`New tour operator "${company}" registered and is waiting for approval on the Murchison Falls platform.`);
    await sendSms(phone, `Thank you for registering ${company}. Your account will be reviewed by the Murchison Falls platform administrators.`);
  } else {
    await sendSms(phone, `Welcome to the Murchison Falls Smart Tourism Platform, ${name.split(' ')[0]}! Plan your trip and book approved tour operators online.`);
  }
  await recordLogin('register', email, id);
  await createSession({ id, role });
  redirect(safeNext(str(fd, 'next')) || dashboardFor(role));
}

export async function logoutAction() {
  const u = await currentUser();
  if (u) await recordLogin('logout', u.email, u.id);
  await destroySession();
  redirect('/');
}

export async function changePasswordAction(_: FormState, fd: FormData): Promise<FormState> {
  const u = await requireUser();
  const row = await one<{ password_hash: string }>('SELECT password_hash FROM users WHERE id = ?', [u.id]);
  const current = String(fd.get('current') ?? '');
  const next = String(fd.get('password') ?? '');
  if (!row || !(await bcrypt.compare(current, row.password_hash))) return { error: 'Your current password is not correct.' };
  if (next.length < 8) return { error: 'The new password must have at least 8 characters.' };
  if (next !== String(fd.get('confirm') ?? '')) return { error: 'The two new passwords do not match.' };
  await run('UPDATE users SET password_hash = ? WHERE id = ?', [await bcrypt.hash(next, 10), u.id]);
  return { ok: 'Your password has been changed.' };
}

export async function updateProfileAction(_: FormState, fd: FormData): Promise<FormState> {
  const u = await requireUser();
  const name = str(fd, 'name', 100);
  const phone = normalizePhone(str(fd, 'phone', 30));
  if (name.length < 2) return { error: 'Enter your full name.' };
  if (!phone) return { error: 'Enter a valid phone number.' };
  await run('UPDATE users SET name = ?, phone = ? WHERE id = ?', [name, phone, u.id]);
  return { ok: 'Your details have been saved.' };
}
