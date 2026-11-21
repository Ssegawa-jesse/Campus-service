import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell, { StatusBadge } from '@/components/DashShell';
import OperatorReview from '@/components/OperatorReview';
import { requireUser } from '@/lib/auth';
import { all, one, type Booking, type Operator } from '@/lib/db';
import { formatShs, paymentsLive } from '@/lib/payments';
import { smsLive } from '@/lib/sms';
import { blobEnabled } from '@/lib/storage';

export const metadata: Metadata = { title: 'Admin dashboard' };

export default async function AdminHome() {
  const u = await requireUser(['admin']);
  const s = await one<Record<string, number>>(`SELECT
    (SELECT COUNT(*) FROM users WHERE role = 'tourist') AS tourists,
    (SELECT COUNT(*) FROM operators WHERE status = 'approved') AS operators,
    (SELECT COUNT(*) FROM operators WHERE status = 'pending') AS pending,
    (SELECT COUNT(*) FROM bookings) AS bookings,
    (SELECT COALESCE(SUM(amount_ugx), 0) FROM payments WHERE status = 'successful') AS revenue,
    (SELECT COUNT(*) FROM scenes WHERE published = 1) AS scenes,
    (SELECT COUNT(*) FROM users WHERE role = 'admin') AS admins,
    (SELECT COUNT(*) FROM login_activities WHERE event = 'login' AND created_at > datetime('now', '-7 days')) AS logins`);
  const pending = await all<Operator & { name: string; email: string }>("SELECT o.*, u.name, u.email FROM operators o JOIN users u ON u.id = o.user_id WHERE o.status = 'pending' ORDER BY o.id");
  const recent = await all<Booking & { company: string; tourist_name: string }>('SELECT b.*, o.company, t.name AS tourist_name FROM bookings b JOIN operators o ON o.id = b.operator_id JOIN users t ON t.id = b.tourist_id ORDER BY b.id DESC LIMIT 6');
  const n = (k: string) => Number(s?.[k] ?? 0);
  return (
    <DashShell user={u} active="/admin" title="Admin dashboard">
      <div className="stats">
        <div className="card"><span className="small muted">Tourists</span><strong>{n('tourists')}</strong></div>
        <div className="card"><span className="small muted">Approved operators</span><strong>{n('operators')}</strong></div>
        <div className="card"><span className="small muted">Bookings</span><strong>{n('bookings')}</strong></div>
        <div className="card"><span className="small muted">Payments received</span><strong style={{ fontSize: '1.1rem' }}>{formatShs(n('revenue'))}</strong></div>
        <div className="card"><span className="small muted">Operators waiting</span><strong style={{ color: n('pending') ? 'var(--sun)' : undefined }}>{n('pending')}</strong></div>
        <div className="card"><span className="small muted">Published 360° scenes</span><strong>{n('scenes')}</strong></div>
        <div className="card"><span className="small muted">Administrators</span><strong>{n('admins')}</strong></div>
        <div className="card"><span className="small muted">Logins (7 days)</span><strong>{n('logins')}</strong></div>
      </div>

      <div className="card" style={{ marginBottom: 16 }}>
        <h3>Tour operators waiting for approval</h3>
        {pending.length === 0 ? <p className="muted small">None — all registrations have been reviewed.</p> : pending.map(o => (
          <div key={o.id} style={{ borderBottom: '1px solid var(--line)', padding: '10px 0' }}>
            <strong>{o.company}</strong> <span className="small muted">— {o.name}, {o.email}, {o.phone}{o.licence_no ? `, licence ${o.licence_no}` : ''}</span>
            {o.description && <div className="small muted">{o.description}</div>}
            <div style={{ marginTop: 6 }}><OperatorReview id={o.id} status={o.status} /></div>
          </div>
        ))}
      </div>

      <div className="card" style={{ marginBottom: 16 }}>
        <h3>Latest bookings</h3>
        {recent.length === 0 ? <p className="muted small">No bookings yet.</p> : (
          <div className="table-wrap"><table className="data"><thead><tr><th>Reference</th><th>Tourist</th><th>Operator</th><th>Travel date</th><th>Status</th></tr></thead><tbody>
            {recent.map(b => <tr key={b.id}><td><Link href={`/admin/bookings/${b.reference}`}>{b.reference}</Link></td><td>{b.tourist_name}</td><td>{b.company}</td><td>{b.start_date}</td><td><StatusBadge s={b.status} /></td></tr>)}
          </tbody></table></div>)}
      </div>

      <div className="card">
        <h3>System status</h3>
        <table className="data"><tbody>
          <tr><th>Payments</th><td>{paymentsLive() ? <span className="status approved">Flutterwave live</span> : <span className="status pending">Simulator (add FLW_SECRET_KEY for real payments)</span>}</td></tr>
          <tr><th>SMS</th><td>{smsLive() ? <span className="status approved">Africa&apos;s Talking live</span> : <span className="status pending">Log only (add AT_USERNAME and AT_API_KEY)</span>}</td></tr>
          <tr><th>Image uploads</th><td>{blobEnabled() ? <span className="status approved">Vercel Blob</span> : <span className="status pending">Local uploads folder</span>}</td></tr>
          <tr><th>AI assistant</th><td>{process.env.ANTHROPIC_API_KEY ? <span className="status approved">Claude</span> : <span className="status pending">Built-in answers (add ANTHROPIC_API_KEY)</span>}</td></tr>
        </tbody></table>
      </div>
    </DashShell>
  );
}
