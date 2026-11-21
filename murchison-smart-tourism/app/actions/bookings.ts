'use server';
import { randomBytes } from 'crypto';
import { redirect } from 'next/navigation';
import { revalidatePath } from 'next/cache';
import { one, run, type Booking, type Operator, type Package } from '@/lib/db';
import { operatorFor, requireUser } from '@/lib/auth';
import { sendSms } from '@/lib/sms';
import { completePayment, failPayment, formatShs, startPayment, type Method } from '@/lib/payments';
import { ACTIVITIES } from '@/lib/data';
import type { FormState } from './auth';

const str = (fd: FormData, k: string, max = 200) => String(fd.get(k) ?? '').trim().slice(0, max);
const int = (fd: FormData, k: string) => Math.trunc(Number(fd.get(k) ?? 0));

async function newReference() {
  for (;;) {
    const ref = 'MF-' + randomBytes(4).toString('hex').toUpperCase().slice(0, 6);
    if (!(await one('SELECT id FROM bookings WHERE reference = ?', [ref]))) return ref;
  }
}

async function bookingContacts(bookingId: number) {
  return one<{ reference: string; tourist_name: string; tourist_phone: string | null; company: string; operator_phone: string | null }>(
    `SELECT b.reference, t.name AS tourist_name, t.phone AS tourist_phone, o.company, COALESCE(o.phone, ou.phone) AS operator_phone
     FROM bookings b JOIN users t ON t.id = b.tourist_id JOIN operators o ON o.id = b.operator_id JOIN users ou ON ou.id = o.user_id WHERE b.id = ?`, [bookingId]);
}

// ---------------- Tourist ----------------

export async function createBookingAction(_: FormState, fd: FormData): Promise<FormState> {
  const u = await requireUser(['tourist']);
  const operatorId = int(fd, 'operator_id');
  const op = await one<Operator>("SELECT * FROM operators WHERE id = ? AND status = 'approved'", [operatorId]);
  if (!op) return { error: 'Choose an approved tour operator.' };
  const packageId = int(fd, 'package_id') || null;
  if (packageId && !(await one<Package>('SELECT id FROM packages WHERE id = ? AND operator_id = ? AND is_active = 1', [packageId, operatorId])))
    return { error: 'That package is no longer available.' };
  const start = str(fd, 'start_date', 10);
  const days = int(fd, 'days'); const adults = int(fd, 'adults'); const children = int(fd, 'children');
  if (!/^\d{4}-\d{2}-\d{2}$/.test(start) || start < new Date().toISOString().slice(0, 10)) return { error: 'Choose a travel date in the future.' };
  if (days < 1 || days > 14) return { error: 'The trip must be between 1 and 14 days.' };
  if (adults < 1 || adults > 30 || children < 0 || children > 30) return { error: 'Check the number of adults and children.' };
  const plan = str(fd, 'plan', 4000) || null;
  const ref = await newReference();
  const { id } = await run(
    'INSERT INTO bookings (reference, tourist_id, operator_id, package_id, plan, start_date, days, adults, children, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [ref, u.id, operatorId, packageId, plan, start, days, adults, children, str(fd, 'message', 1500) || null]);
  const c = await bookingContacts(id);
  if (c) {
    await sendSms(c.operator_phone, `New Murchison Falls enquiry ${ref} from ${c.tourist_name}: ${days} day(s) from ${start}, ${adults + children} person(s). Send a quote in your dashboard.`);
    await sendSms(c.tourist_phone, `Your enquiry ${ref} has been sent to ${c.company}. You will receive an SMS when they send a quote.`);
  }
  redirect(`/tourist/bookings/${ref}`);
}

async function touristBooking(ref: string) {
  const u = await requireUser(['tourist']);
  const b = await one<Booking>('SELECT * FROM bookings WHERE reference = ? AND tourist_id = ?', [ref, u.id]);
  if (!b) throw new Error('Booking not found');
  return { u, b };
}

export async function cancelBookingAction(fd: FormData) {
  const { b } = await touristBooking(str(fd, 'reference', 20));
  if (b.status === 'enquiry' || b.status === 'quoted') {
    await run("UPDATE bookings SET status = 'cancelled', updated_at = datetime('now') WHERE id = ?", [b.id]);
    const c = await bookingContacts(b.id);
    await sendSms(c?.operator_phone, `Booking ${b.reference} was cancelled by the tourist.`);
  }
  revalidatePath(`/tourist/bookings/${b.reference}`);
}

export async function payAction(_: FormState, fd: FormData): Promise<FormState> {
  const { u, b } = await touristBooking(str(fd, 'reference', 20));
  const method = str(fd, 'method') as Method;
  if (!['mtn', 'airtel', 'card'].includes(method)) return { error: 'Choose a payment method.' };
  let url: string;
  try { url = await startPayment(b, u, method, str(fd, 'phone', 30)); }
  catch (e) { return { error: e instanceof Error ? e.message : 'The payment could not be started.' }; }
  redirect(url);
}

/** Demo payment screen: approves or declines a simulated mobile money / card payment. */
export async function simulatePaymentAction(fd: FormData) {
  const u = await requireUser(['tourist']);
  const txRef = str(fd, 'tx_ref', 60);
  const p = await one<{ reference: string; provider: string }>(
    'SELECT b.reference, p.provider FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE p.tx_ref = ? AND b.tourist_id = ?', [txRef, u.id]);
  if (!p || p.provider !== 'simulator') redirect('/tourist');
  if (str(fd, 'decision') === 'approve') await completePayment(txRef, 'SIM-' + randomBytes(3).toString('hex').toUpperCase());
  else await failPayment(txRef);
  redirect(`/tourist/bookings/${p.reference}`);
}

// ---------------- Operator ----------------

async function operatorBooking(ref: string) {
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  if (!op || op.status !== 'approved') throw new Error('Your operator account is not approved yet.');
  const b = await one<Booking>('SELECT * FROM bookings WHERE reference = ? AND operator_id = ?', [ref, op.id]);
  if (!b) throw new Error('Booking not found');
  return { op, b };
}

export async function quoteAction(_: FormState, fd: FormData): Promise<FormState> {
  const { op, b } = await operatorBooking(str(fd, 'reference', 20));
  if (b.status !== 'enquiry' && b.status !== 'quoted') return { error: 'This booking can no longer be quoted.' };
  const amount = int(fd, 'quote_ugx');
  if (amount < 10000 || amount > 500000000) return { error: 'Enter the total price in Uganda shillings (at least UGX 10,000).' };
  await run("UPDATE bookings SET status = 'quoted', quote_ugx = ?, quote_note = ?, updated_at = datetime('now') WHERE id = ?", [amount, str(fd, 'quote_note', 1500) || null, b.id]);
  const c = await bookingContacts(b.id);
  await sendSms(c?.tourist_phone, `${op.company} has quoted ${formatShs(amount)} for your Murchison Falls trip ${b.reference}. Log in to pay by Mobile Money or card.`);
  revalidatePath(`/operator/bookings/${b.reference}`);
  return { ok: 'Quote sent. The tourist has been notified by SMS.' };
}

export async function operatorStatusAction(fd: FormData) {
  const { op, b } = await operatorBooking(str(fd, 'reference', 20));
  const to = str(fd, 'to');
  const allowed: Record<string, string[]> = { declined: ['enquiry', 'quoted'], confirmed: ['paid'], completed: ['confirmed'] };
  if (!allowed[to]?.includes(b.status)) return;
  await run("UPDATE bookings SET status = ?, updated_at = datetime('now') WHERE id = ?", [to, b.id]);
  const c = await bookingContacts(b.id);
  const msg: Record<string, string> = {
    declined: `${op.company} cannot take booking ${b.reference}. Please choose another operator on the Murchison Falls platform.`,
    confirmed: `Your Murchison Falls trip ${b.reference} starting ${b.start_date} is CONFIRMED by ${op.company}. Have a great safari!`,
    completed: `Thank you for visiting Murchison Falls with ${op.company} (booking ${b.reference}).`,
  };
  await sendSms(c?.tourist_phone, msg[to]);
  revalidatePath(`/operator/bookings/${b.reference}`);
}

export async function savePackageAction(_: FormState, fd: FormData): Promise<FormState> {
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  if (!op) return { error: 'Operator profile not found.' };
  const title = str(fd, 'title', 120);
  const days = int(fd, 'days');
  const price = int(fd, 'price_ugx');
  const acts = fd.getAll('activities').map(String).filter(a => ACTIVITIES.some(x => x.id === a));
  if (title.length < 3) return { error: 'Enter a package title.' };
  if (days < 1 || days > 14) return { error: 'Days must be between 1 and 14.' };
  if (price < 10000) return { error: 'Enter the price per person in Uganda shillings.' };
  const id = int(fd, 'id');
  if (id) {
    const r = await run('UPDATE packages SET title = ?, days = ?, price_ugx = ?, activities = ?, description = ? WHERE id = ? AND operator_id = ?',
      [title, days, price, acts.join(','), str(fd, 'description', 1500) || null, id, op.id]);
    if (!r.changes) return { error: 'Package not found.' };
  } else {
    await run('INSERT INTO packages (operator_id, title, days, price_ugx, activities, description) VALUES (?, ?, ?, ?, ?, ?)',
      [op.id, title, days, price, acts.join(','), str(fd, 'description', 1500) || null]);
  }
  revalidatePath('/operator/packages');
  return { ok: id ? 'Package updated.' : 'Package added.' };
}

export async function togglePackageAction(fd: FormData) {
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  if (!op) return;
  await run('UPDATE packages SET is_active = 1 - is_active WHERE id = ? AND operator_id = ?', [int(fd, 'id'), op.id]);
  revalidatePath('/operator/packages');
}

export async function operatorProfileAction(_: FormState, fd: FormData): Promise<FormState> {
  const u = await requireUser(['operator']);
  const company = str(fd, 'company', 120);
  if (company.length < 2) return { error: 'Enter your company name.' };
  await run('UPDATE operators SET company = ?, licence_no = ?, description = ? WHERE user_id = ?',
    [company, str(fd, 'licence', 60) || null, str(fd, 'description', 1500) || null, u.id]);
  return { ok: 'Company profile saved.' };
}
