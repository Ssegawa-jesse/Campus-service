import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import DashShell from '@/components/DashShell';
import BookingDetail, { loadBooking } from '@/components/BookingDetail';
import { PayForm } from '@/components/forms/BookingForms';
import { requireUser } from '@/lib/auth';
import { cancelBookingAction } from '@/app/actions/bookings';
import { formatShs, paymentsLive } from '@/lib/payments';

export const metadata: Metadata = { title: 'Booking' };

export default async function TouristBooking({ params }: { params: Promise<{ ref: string }> }) {
  const { ref } = await params;
  const u = await requireUser(['tourist']);
  const data = await loadBooking('b.reference = ? AND b.tourist_id = ?', [ref, u.id]);
  if (!data) notFound();
  const { b } = data;
  return (
    <DashShell user={u} active="/tourist" title={`Booking ${b.reference}`}>
      {b.status === 'enquiry' && <div className="notice info">Your enquiry has been sent to {b.company}. You will receive an SMS when they send a quote.</div>}
      {b.status === 'paid' && <div className="notice info">Payment received. {b.company} will confirm your trip.</div>}
      {b.status === 'confirmed' && <div className="notice info">🎉 Your trip is confirmed. Have a wonderful time at Murchison Falls!</div>}
      <BookingDetail data={data} />
      <div className="grid grid-2" style={{ marginTop: 16 }}>
        {b.status === 'quoted' && b.quote_ugx && <PayForm reference={b.reference} amount={formatShs(b.quote_ugx)} live={paymentsLive()} />}
        {(b.status === 'enquiry' || b.status === 'quoted') && (
          <form action={cancelBookingAction} className="card">
            <h3>Cancel this booking</h3>
            <p className="small muted">You can cancel before paying.</p>
            <input type="hidden" name="reference" value={b.reference} />
            <button className="btn btn-danger">Cancel booking</button>
          </form>
        )}
      </div>
    </DashShell>
  );
}
