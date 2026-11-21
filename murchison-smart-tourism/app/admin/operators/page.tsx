import type { Metadata } from 'next';
import DashShell, { StatusBadge } from '@/components/DashShell';
import OperatorReview from '@/components/OperatorReview';
import { requireUser } from '@/lib/auth';
import { all, type Operator } from '@/lib/db';

export const metadata: Metadata = { title: 'Tour operators' };

export default async function AdminOperators() {
  const u = await requireUser(['admin']);
  const ops = await all<Operator & { name: string; email: string; pkgs: number; bookings: number }>(
    `SELECT o.*, u.name, u.email, (SELECT COUNT(*) FROM packages p WHERE p.operator_id = o.id) AS pkgs, (SELECT COUNT(*) FROM bookings b WHERE b.operator_id = o.id) AS bookings
     FROM operators o JOIN users u ON u.id = o.user_id ORDER BY CASE o.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END, o.company`);
  return (
    <DashShell user={u} active="/admin/operators" title="Tour operators">
      <div className="card table-wrap">
        {ops.length === 0 ? <p className="muted">No tour operators have registered yet.</p> : (
          <table className="data"><thead><tr><th>Company</th><th>Contact</th><th>Packages / bookings</th><th>Status</th><th>Action</th></tr></thead><tbody>
            {ops.map(o => (
              <tr key={o.id}>
                <td><strong>{o.company}</strong>{o.licence_no && <div className="small muted">Licence {o.licence_no}</div>}{o.review_note && <div className="small muted">Note: {o.review_note}</div>}</td>
                <td className="small">{o.name}<br />{o.email}<br />{o.phone}</td>
                <td>{o.pkgs} / {o.bookings}</td>
                <td><StatusBadge s={o.status} /><div className="small muted">since {o.created_at.slice(0, 10)}</div></td>
                <td><OperatorReview id={o.id} status={o.status} /></td>
              </tr>
            ))}
          </tbody></table>)}
      </div>
    </DashShell>
  );
}
