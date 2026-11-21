import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import DashShell from '@/components/DashShell';
import BookingDetail, { loadBooking } from '@/components/BookingDetail';
import { requireUser } from '@/lib/auth';

export const metadata: Metadata = { title: 'Booking' };

export default async function AdminBooking({ params }: { params: Promise<{ ref: string }> }) {
  const { ref } = await params;
  const u = await requireUser(['admin']);
  const data = await loadBooking('b.reference = ?', [ref]);
  if (!data) notFound();
  return <DashShell user={u} active="/admin/bookings" title={`Booking ${data.b.reference}`}><BookingDetail data={data} showTourist /></DashShell>;
}
