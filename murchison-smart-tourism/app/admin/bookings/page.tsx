import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell, { StatusBadge } from '@/components/DashShell';
import { requireUser } from '@/lib/auth';
import { all, type Booking } from '@/lib/db';
import { formatShs } from '@/lib/payments';

export const metadata: Metadata = { title: 'Bookings' };

export default async function AdminBookings() {
  const u = await requireUser(['admin']);
  const rows = await all<Booking & { company: string; tourist_name: string }>('SELECT b.*, o.company, t.name AS tourist_name FROM bookings b JOIN operators o ON o.id = b.operator_id JOIN users t ON t.id = b.tourist_id ORDER BY b.id DESC');
  return (
    <DashShell user={u} active="/admin/bookings" title="All bookings">
      <div className="card table-wrap">
        {rows.length === 0 ? <p className="muted">No bookings yet.</p> : (
          <table className="data"><thead><tr><th>Reference</th><th>Tourist</th><th>Operator</th><th>Travel date</th><th>People</th><th>Quote</th><th>Itinerary</th><th>Status</th></tr></thead><tbody>
            {rows.map(b => <tr key={b.id}><td><Link href={`/admin/bookings/${b.reference}`}>{b.reference}</Link></td><td>{b.tourist_name}</td><td>{b.company}</td><td>{b.start_date}</td><td>{b.adults + b.children}</td><td>{b.quote_ugx ? formatShs(b.quote_ugx) : '—'}</td><td>{b.plan ? 'Smart plan' : '—'}</td><td><StatusBadge s={b.status} /></td></tr>)}
          </tbody></table>)}
      </div>
    </DashShell>
  );
}
