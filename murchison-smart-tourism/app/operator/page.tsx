import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell, { StatusBadge } from '@/components/DashShell';
import OperatorGate from '@/components/OperatorGate';
import { operatorFor, requireUser } from '@/lib/auth';
import { all, one, type Booking } from '@/lib/db';
import { formatShs } from '@/lib/payments';

export const metadata: Metadata = { title: 'Operator dashboard' };

export default async function OperatorHome() {
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  const id = op?.id ?? 0;
  const rows = await all<Booking & { tourist_name: string }>('SELECT b.*, t.name AS tourist_name FROM bookings b JOIN users t ON t.id = b.tourist_id WHERE b.operator_id = ? ORDER BY b.id DESC LIMIT 8', [id]);
  const s = await one<{ enquiries: number; active: number; revenue: number; pkgs: number }>(
    `SELECT (SELECT COUNT(*) FROM bookings WHERE operator_id = ? AND status = 'enquiry') AS enquiries,
            (SELECT COUNT(*) FROM bookings WHERE operator_id = ? AND status IN ('paid','confirmed')) AS active,
            (SELECT COALESCE(SUM(p.amount_ugx),0) FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE b.operator_id = ? AND p.status = 'successful') AS revenue,
            (SELECT COUNT(*) FROM packages WHERE operator_id = ? AND is_active = 1) AS pkgs`, [id, id, id, id]);
  return (
    <DashShell user={u} active="/operator" title={op?.company ?? 'Operator dashboard'}>
      <OperatorGate op={op} />
      {op?.status === 'approved' && <div className="notice info">✅ Your company is approved and visible to tourists.</div>}
      <div className="stats">
        <div className="card"><span className="small muted">New enquiries</span><strong>{s?.enquiries ?? 0}</strong></div>
        <div className="card"><span className="small muted">Paid / confirmed</span><strong>{s?.active ?? 0}</strong></div>
        <div className="card"><span className="small muted">Revenue received</span><strong style={{ fontSize: '1.1rem' }}>{formatShs(Number(s?.revenue ?? 0))}</strong></div>
        <div className="card"><span className="small muted">Active packages</span><strong>{s?.pkgs ?? 0}</strong></div>
      </div>
      <div className="card">
        <h3>Latest bookings</h3>
        {rows.length === 0 ? <p className="muted">No bookings yet.</p> : (
          <div className="table-wrap"><table className="data"><thead><tr><th>Reference</th><th>Tourist</th><th>Travel date</th><th>People</th><th>Status</th></tr></thead><tbody>
            {rows.map(b => <tr key={b.id}><td><Link href={`/operator/bookings/${b.reference}`}>{b.reference}</Link></td><td>{b.tourist_name}</td><td>{b.start_date}</td><td>{b.adults + b.children}</td><td><StatusBadge s={b.status} /></td></tr>)}
          </tbody></table></div>)}
        <Link href="/operator/bookings" className="small">All bookings →</Link>
      </div>
    </DashShell>
  );
}
