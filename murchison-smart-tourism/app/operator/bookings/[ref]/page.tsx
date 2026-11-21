import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import DashShell from '@/components/DashShell';
import BookingDetail, { loadBooking } from '@/components/BookingDetail';
import OperatorGate from '@/components/OperatorGate';
import { QuoteForm } from '@/components/forms/BookingForms';
import { operatorFor, requireUser } from '@/lib/auth';
import { operatorStatusAction } from '@/app/actions/bookings';

export const metadata: Metadata = { title: 'Booking' };

export default async function OperatorBooking({ params }: { params: Promise<{ ref: string }> }) {
  const { ref } = await params;
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  const data = await loadBooking('b.reference = ? AND b.operator_id = ?', [ref, op?.id ?? 0]);
  if (!data) notFound();
  const { b, pkg, plan } = data;
  const approved = op?.status === 'approved';
  const suggested = pkg ? pkg.price_ugx * (b.adults + b.children) : plan ? Math.round(plan.plan.totalUgx / 1000) * 1000 : undefined;
  const Act = ({ to, label, danger }: { to: string; label: string; danger?: boolean }) => (
    <form action={operatorStatusAction} className="inline-form"><input type="hidden" name="reference" value={b.reference} /><input type="hidden" name="to" value={to} />
      <button className={`btn ${danger ? 'btn-danger' : 'btn-primary'}`}>{label}</button></form>);
  return (
    <DashShell user={u} active="/operator/bookings" title={`Booking ${b.reference}`}>
      <OperatorGate op={op} />
      <BookingDetail data={data} showTourist />
      {approved && (
        <div className="grid grid-2" style={{ marginTop: 16 }}>
          {(b.status === 'enquiry' || b.status === 'quoted') && <QuoteForm reference={b.reference} current={b.quote_ugx} note={b.quote_note} suggested={suggested} />}
          <div className="card">
            <h3>Next step</h3>
            <div className="btn-row">
              {b.status === 'paid' && <Act to="confirmed" label="Confirm the trip" />}
              {b.status === 'confirmed' && <Act to="completed" label="Mark as completed" />}
              {(b.status === 'enquiry' || b.status === 'quoted') && <Act to="declined" label="Decline" danger />}
            </div>
            <p className="small muted" style={{ marginTop: 8 }}>The tourist receives an SMS at every step.</p>
          </div>
        </div>
      )}
    </DashShell>
  );
}
