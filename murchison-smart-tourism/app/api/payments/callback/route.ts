import { NextResponse } from 'next/server';
import { one, type Payment } from '@/lib/db';
import { completePayment, failPayment, paymentsLive, verifyFlutterwave } from '@/lib/payments';

/** Flutterwave sends the payer back here. The payment is verified with Flutterwave before the booking is marked as paid. */
export async function GET(req: Request) {
  const url = new URL(req.url);
  const txRef = url.searchParams.get('tx_ref') || '';
  const status = url.searchParams.get('status') || '';
  const transactionId = url.searchParams.get('transaction_id') || '';
  const p = await one<Payment & { reference: string }>('SELECT p.*, b.reference FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE p.tx_ref = ?', [txRef]);
  if (!p) return NextResponse.redirect(new URL('/tourist', url));
  if (paymentsLive() && transactionId && /success|completed/i.test(status)) {
    const v = await verifyFlutterwave(transactionId, txRef);
    if (v.ok) await completePayment(txRef, v.providerRef);
    else await failPayment(txRef);
  } else if (/cancel|fail/i.test(status)) {
    await failPayment(txRef);
  }
  return NextResponse.redirect(new URL(`/tourist/bookings/${p.reference}`, url));
}
