import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell, { StatusBadge } from '@/components/DashShell';
import { requireUser } from '@/lib/auth';
import { all, type Booking } from '@/lib/db';
import { formatShs } from '@/lib/payments';

export const metadata: Metadata = { title: 'My bookings' };

export default async function TouristHome() {
  const u = await requireUser(['tourist']);
  const rows = await all<Booking & { company: string }>('SELECT b.*, o.company FROM bookings b JOIN operators o ON o.id = b.operator_id WHERE b.tourist_id = ? ORDER BY b.id DESC', [u.id]);
  return (
    <DashShell user={u} active="/tourist" title={`Welcome, ${u.name.split(' ')[0]}`}>
      <div className="btn-row" style={{ marginBottom: 16 }}>
        <Link href="/plan" className="btn btn-primary">Plan a new trip</Link>
        <Link href="/operators" className="btn btn-ghost">Browse tour operators</Link>
      </div>
      <div className="card">
        <h3>My bookings</h3>
        {rows.length === 0 ? <p className="muted">No bookings yet. Build an itinerary with the smart planner and press “Book this itinerary”.</p> : (
          <div className="table-wrap"><table className="data">
            <thead><tr><th>Reference</th><th>Operator</th><th>Travel date</th><th>People</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>{rows.map(b => (
              <tr key={b.id}><td><Link href={`/tourist/bookings/${b.reference}`}>{b.reference}</Link></td><td>{b.company}</td><td>{b.start_date} ({b.days} d)</td>
                <td>{b.adults + b.children}</td><td>{b.quote_ugx ? formatShs(b.quote_ugx) : '—'}</td><td><StatusBadge s={b.status} /></td></tr>
            ))}</tbody>
          </table></div>
        )}
      </div>
    </DashShell>
  );
}
