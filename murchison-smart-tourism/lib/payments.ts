import 'server-only';
import { randomBytes } from 'crypto';
import { headers } from 'next/headers';
import { one, run, type Booking, type Payment, type User } from './db';
import { normalizePhone, sendSms } from './sms';

export const METHODS = { mtn: 'MTN Mobile Money', airtel: 'Airtel Money', card: 'Visa / Mastercard' } as const;
export type Method = keyof typeof METHODS;

/** Live payments run through Flutterwave when FLW_SECRET_KEY is set; otherwise a payment simulator is used. */
export const paymentsLive = () => !!process.env.FLW_SECRET_KEY;

export async function baseUrl() {
  if (process.env.APP_URL) return process.env.APP_URL.replace(/\/$/, '');
  const h = await headers();
  const host = h.get('x-forwarded-host') || h.get('host') || 'localhost:3000';
  const proto = h.get('x-forwarded-proto') || (host.startsWith('localhost') ? 'http' : 'https');
  return `${proto}://${host}`;
}

export const formatShs = (n: number) => 'UGX ' + Math.round(n).toLocaleString('en-US');

/** Creates a pending payment and returns the address the payer must be sent to. */
export async function startPayment(booking: Booking, tourist: User, method: Method, phone: string | null): Promise<string> {
  if (booking.status !== 'quoted' || !booking.quote_ugx) throw new Error('This booking cannot be paid yet.');
  const msisdn = method === 'card' ? null : normalizePhone(phone);
  if (method !== 'card' && !msisdn) throw new Error('Enter a valid mobile money number, for example 0772 123 456.');
  const txRef = `MF-${booking.reference}-${randomBytes(4).toString('hex').toUpperCase()}`;
  const provider = paymentsLive() ? 'flutterwave' : 'simulator';
  await run('INSERT INTO payments (booking_id, method, phone, amount_ugx, status, provider, tx_ref) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [booking.id, method, msisdn, booking.quote_ugx, 'pending', provider, txRef]);

  if (provider === 'simulator') return `/pay/simulate/${txRef}`;

  const base = await baseUrl();
  const res = await fetch('https://api.flutterwave.com/v3/payments', {
    method: 'POST',
    headers: { Authorization: `Bearer ${process.env.FLW_SECRET_KEY}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({
      tx_ref: txRef, amount: booking.quote_ugx, currency: 'UGX',
      redirect_url: `${base}/api/payments/callback`,
      payment_options: method === 'card' ? 'card' : 'mobilemoneyuganda',
      customer: { email: tourist.email, name: tourist.name, phonenumber: msisdn ?? tourist.phone ?? undefined },
      customizations: { title: 'Murchison Falls trip', description: `Booking ${booking.reference}` },
      meta: { booking_reference: booking.reference },
    }),
  });
  const json = await res.json().catch(() => null);
  if (!res.ok || json?.status !== 'success' || !json?.data?.link) {
    await run("UPDATE payments SET status = 'failed', updated_at = datetime('now') WHERE tx_ref = ?", [txRef]);
    throw new Error('The payment service could not start the payment. Please try again.');
  }
  return json.data.link as string;
}

/** Asks Flutterwave whether a transaction really succeeded (never trust the redirect alone). */
export async function verifyFlutterwave(transactionId: string, txRef: string) {
  const res = await fetch(`https://api.flutterwave.com/v3/transactions/${encodeURIComponent(transactionId)}/verify`, {
    headers: { Authorization: `Bearer ${process.env.FLW_SECRET_KEY}` },
  });
  const json = await res.json().catch(() => null);
  const d = json?.data;
  const p = await one<Payment>('SELECT * FROM payments WHERE tx_ref = ?', [txRef]);
  const ok = !!(res.ok && d && d.status === 'successful' && d.tx_ref === txRef && d.currency === 'UGX' && p && Number(d.amount) >= p.amount_ugx);
  return { ok, providerRef: d?.flw_ref ? String(d.flw_ref) : String(transactionId) };
}

/** Marks a payment as successful once, moves the booking to "paid" and notifies the tourist and operator. */
export async function completePayment(txRef: string, providerRef: string | null) {
  const p = await one<Payment>('SELECT * FROM payments WHERE tx_ref = ?', [txRef]);
  if (!p || p.status !== 'pending') return p;
  await run("UPDATE payments SET status = 'successful', provider_ref = ?, updated_at = datetime('now') WHERE id = ? AND status = 'pending'", [providerRef, p.id]);
  await run("UPDATE bookings SET status = 'paid', updated_at = datetime('now') WHERE id = ? AND status = 'quoted'", [p.booking_id]);
  const info = await one<{ reference: string; tourist_phone: string | null; operator_phone: string | null; company: string }>(
    `SELECT b.reference, t.phone AS tourist_phone, COALESCE(o.phone, ou.phone) AS operator_phone, o.company
     FROM bookings b JOIN users t ON t.id = b.tourist_id JOIN operators o ON o.id = b.operator_id JOIN users ou ON ou.id = o.user_id WHERE b.id = ?`, [p.booking_id]);
  if (info) {
    await sendSms(info.tourist_phone, `Payment of ${formatShs(p.amount_ugx)} received for Murchison Falls booking ${info.reference}. ${info.company} will confirm your trip shortly.`);
    await sendSms(info.operator_phone, `Booking ${info.reference} has been paid (${formatShs(p.amount_ugx)}). Please confirm the trip in your dashboard.`);
  }
  return one<Payment>('SELECT * FROM payments WHERE id = ?', [p.id]);
}

export async function failPayment(txRef: string) {
  await run("UPDATE payments SET status = 'failed', updated_at = datetime('now') WHERE tx_ref = ? AND status = 'pending'", [txRef]);
}
