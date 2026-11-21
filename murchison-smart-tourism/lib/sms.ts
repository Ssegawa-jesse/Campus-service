import 'server-only';
import { all, run } from './db';

/** Turns 0772123456, 256772123456 or +256 772 123 456 into +256772123456. */
export function normalizePhone(raw: string | null | undefined): string | null {
  if (!raw) return null;
  const d = raw.replace(/[^\d+]/g, '');
  if (/^\+\d{9,15}$/.test(d)) return d;
  if (/^0\d{9}$/.test(d)) return '+256' + d.slice(1);
  if (/^256\d{9}$/.test(d)) return '+' + d;
  if (/^\d{9}$/.test(d)) return '+256' + d;
  return null;
}

export const smsLive = () => !!(process.env.AT_USERNAME && process.env.AT_API_KEY);

/**
 * Sends an SMS through Africa's Talking when AT_USERNAME and AT_API_KEY are set; otherwise the message is only
 * recorded in the SMS log so the system can be demonstrated without an SMS account. Never throws.
 */
export async function sendSms(phone: string | null | undefined, message: string) {
  const to = normalizePhone(phone);
  if (!to) return;
  const text = message.slice(0, 459);
  if (!smsLive()) {
    await run('INSERT INTO sms_logs (phone, message, status, provider, detail) VALUES (?, ?, ?, ?, ?)', [to, text, 'logged', 'log', 'Demo mode: add AT_USERNAME and AT_API_KEY to send real SMS']);
    return;
  }
  const username = process.env.AT_USERNAME!;
  const host = username === 'sandbox' ? 'https://api.sandbox.africastalking.com' : 'https://api.africastalking.com';
  try {
    const body = new URLSearchParams({ username, to, message: text });
    if (process.env.AT_SENDER_ID) body.set('from', process.env.AT_SENDER_ID);
    const res = await fetch(`${host}/version1/messaging`, {
      method: 'POST',
      headers: { apiKey: process.env.AT_API_KEY!, Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
      body,
    });
    const json = await res.json().catch(() => null);
    const recipient = json?.SMSMessageData?.Recipients?.[0];
    const ok = res.ok && recipient && /success/i.test(recipient.status);
    await run('INSERT INTO sms_logs (phone, message, status, provider, detail) VALUES (?, ?, ?, ?, ?)',
      [to, text, ok ? 'sent' : 'failed', 'africastalking', recipient?.status || json?.SMSMessageData?.Message || `HTTP ${res.status}`]);
  } catch (e) {
    await run('INSERT INTO sms_logs (phone, message, status, provider, detail) VALUES (?, ?, ?, ?, ?)', [to, text, 'failed', 'africastalking', String(e).slice(0, 200)]);
  }
}

/** Sends the same SMS to every active administrator who has a phone number. */
export async function smsAdmins(message: string) {
  const admins = await all<{ phone: string | null }>("SELECT phone FROM users WHERE role = 'admin' AND is_active = 1 AND phone IS NOT NULL");
  for (const a of admins) await sendSms(a.phone, message);
}
