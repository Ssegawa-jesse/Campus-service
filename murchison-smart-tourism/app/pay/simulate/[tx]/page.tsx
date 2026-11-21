import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { requireUser } from '@/lib/auth';
import { one, type Payment } from '@/lib/db';
import { formatShs, METHODS } from '@/lib/payments';
import { simulatePaymentAction } from '@/app/actions/bookings';

export const metadata: Metadata = { title: 'Approve payment' };

export default async function SimulatePayment({ params }: { params: Promise<{ tx: string }> }) {
  const { tx } = await params;
  const u = await requireUser(['tourist']);
  const p = await one<Payment & { reference: string }>(
    "SELECT p.*, b.reference FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE p.tx_ref = ? AND b.tourist_id = ? AND p.provider = 'simulator'", [tx, u.id]);
  if (!p) notFound();
  return (
    <section className="section"><div className="container auth-wrap">
      <div className="card center">
        <div className="notice warn">Demo payment — no real money is moved. Add the Flutterwave key to accept real payments.</div>
        <div style={{ fontSize: 44 }}>{p.method === 'card' ? '💳' : '📱'}</div>
        <h2>{p.method === 'card' ? 'Card payment' : `${METHODS[p.method]} prompt`}</h2>
        <p>{p.method === 'card' ? 'Approve the card payment' : `A payment request has been sent to ${p.phone}. Enter your PIN on the phone to approve`} for booking <strong>{p.reference}</strong>.</p>
        <p style={{ fontSize: '1.6rem', fontWeight: 700, color: 'var(--green)' }}>{formatShs(p.amount_ugx)}</p>
        {p.status === 'pending' ? (
          <form action={simulatePaymentAction} className="btn-row" style={{ justifyContent: 'center' }}>
            <input type="hidden" name="tx_ref" value={p.tx_ref} />
            <button className="btn btn-primary" name="decision" value="approve">Approve payment</button>
            <button className="btn btn-danger" name="decision" value="decline">Decline</button>
          </form>
        ) : <p>This payment is already <strong>{p.status}</strong>.</p>}
      </div>
    </div></section>
  );
}
