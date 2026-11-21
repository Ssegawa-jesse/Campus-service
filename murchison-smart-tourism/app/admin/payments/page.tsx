import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell, { StatusBadge } from '@/components/DashShell';
import { requireUser } from '@/lib/auth';
import { all, one, type Payment } from '@/lib/db';
import { formatShs, METHODS, paymentsLive } from '@/lib/payments';

export const metadata: Metadata = { title: 'Payments' };

export default async function AdminPayments() {
  const u = await requireUser(['admin']);
  const rows = await all<Payment & { reference: string; company: string }>('SELECT p.*, b.reference, o.company FROM payments p JOIN bookings b ON b.id = p.booking_id JOIN operators o ON o.id = b.operator_id ORDER BY p.id DESC');
  const t = await one<{ total: number; mtn: number; airtel: number; card: number }>(`SELECT COALESCE(SUM(amount_ugx),0) AS total,
    COALESCE(SUM(CASE WHEN method='mtn' THEN amount_ugx END),0) AS mtn, COALESCE(SUM(CASE WHEN method='airtel' THEN amount_ugx END),0) AS airtel,
    COALESCE(SUM(CASE WHEN method='card' THEN amount_ugx END),0) AS card FROM payments WHERE status = 'successful'`);
  return (
    <DashShell user={u} active="/admin/payments" title="Payments">
      {!paymentsLive() && <div className="notice warn">Payments are in demo (simulator) mode. Add FLW_SECRET_KEY to accept real MTN Mobile Money, Airtel Money and card payments through Flutterwave.</div>}
      <div className="stats">
        <div className="card"><span className="small muted">Total received</span><strong style={{ fontSize: '1.1rem' }}>{formatShs(Number(t?.total ?? 0))}</strong></div>
        <div className="card"><span className="small muted">MTN Mobile Money</span><strong style={{ fontSize: '1.1rem' }}>{formatShs(Number(t?.mtn ?? 0))}</strong></div>
        <div className="card"><span className="small muted">Airtel Money</span><strong style={{ fontSize: '1.1rem' }}>{formatShs(Number(t?.airtel ?? 0))}</strong></div>
        <div className="card"><span className="small muted">Card</span><strong style={{ fontSize: '1.1rem' }}>{formatShs(Number(t?.card ?? 0))}</strong></div>
      </div>
      <div className="card table-wrap">
        {rows.length === 0 ? <p className="muted">No payments yet.</p> : (
          <table className="data"><thead><tr><th>Date</th><th>Booking</th><th>Operator</th><th>Method</th><th>Amount</th><th>Provider</th><th>Status</th></tr></thead><tbody>
            {rows.map(p => <tr key={p.id}><td className="small">{p.created_at}</td><td><Link href={`/admin/bookings/${p.reference}`}>{p.reference}</Link></td><td>{p.company}</td><td>{METHODS[p.method]}<div className="small muted">{p.phone}</div></td><td>{formatShs(p.amount_ugx)}</td><td className="small">{p.provider}<div className="muted">{p.provider_ref}</div></td><td><StatusBadge s={p.status} /></td></tr>)}
          </tbody></table>)}
      </div>
    </DashShell>
  );
}
