import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell, { StatusBadge } from '@/components/DashShell';
import OperatorGate from '@/components/OperatorGate';
import { operatorFor, requireUser } from '@/lib/auth';
import { all, type Booking } from '@/lib/db';
import { formatShs } from '@/lib/payments';

export const metadata: Metadata = { title: 'Bookings' };

export default async function OperatorBookings() {
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  const rows = await all<Booking & { tourist_name: string }>('SELECT b.*, t.name AS tourist_name FROM bookings b JOIN users t ON t.id = b.tourist_id WHERE b.operator_id = ? ORDER BY b.id DESC', [op?.id ?? 0]);
  return (
    <DashShell user={u} active="/operator/bookings" title="Bookings">
      <OperatorGate op={op} />
      <div className="card table-wrap">
        {rows.length === 0 ? <p className="muted">No bookings yet.</p> : (
          <table className="data"><thead><tr><th>Reference</th><th>Tourist</th><th>Travel date</th><th>People</th><th>Quote</th><th>Status</th></tr></thead><tbody>
            {rows.map(b => <tr key={b.id}><td><Link href={`/operator/bookings/${b.reference}`}>{b.reference}</Link></td><td>{b.tourist_name}</td><td>{b.start_date} ({b.days} d)</td><td>{b.adults + b.children}</td><td>{b.quote_ugx ? formatShs(b.quote_ugx) : '—'}</td><td><StatusBadge s={b.status} /></td></tr>)}
          </tbody></table>)}
      </div>
    </DashShell>
  );
}
