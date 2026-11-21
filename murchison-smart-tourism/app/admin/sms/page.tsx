import type { Metadata } from 'next';
import DashShell, { StatusBadge } from '@/components/DashShell';
import { TestSmsForm } from '@/components/forms/AdminForms';
import { requireUser } from '@/lib/auth';
import { all } from '@/lib/db';
import { smsLive } from '@/lib/sms';

export const metadata: Metadata = { title: 'SMS log' };

export default async function AdminSms() {
  const u = await requireUser(['admin']);
  const rows = await all<{ id: number; phone: string; message: string; status: string; provider: string; detail: string | null; created_at: string }>('SELECT * FROM sms_logs ORDER BY id DESC LIMIT 200');
  return (
    <DashShell user={u} active="/admin/sms" title="SMS notifications">
      <div className={`notice ${smsLive() ? 'info' : 'warn'}`}>{smsLive() ? "SMS are sent through Africa's Talking." : "SMS are only recorded here (demo mode). Add AT_USERNAME and AT_API_KEY from Africa's Talking to send real SMS."}</div>
      <TestSmsForm />
      <div className="card table-wrap" style={{ marginTop: 16 }}>
        {rows.length === 0 ? <p className="muted">No messages yet.</p> : (
          <table className="data"><thead><tr><th>Date</th><th>Phone</th><th>Message</th><th>Status</th></tr></thead><tbody>
            {rows.map(r => <tr key={r.id}><td className="small">{r.created_at}</td><td>{r.phone}</td><td className="small">{r.message}</td><td><StatusBadge s={r.status} /><div className="small muted">{r.detail}</div></td></tr>)}
          </tbody></table>)}
      </div>
    </DashShell>
  );
}
