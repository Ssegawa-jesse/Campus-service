import type { Metadata } from 'next';
import DashShell from '@/components/DashShell';
import { requireUser } from '@/lib/auth';
import { all } from '@/lib/db';

export const metadata: Metadata = { title: 'Login activity' };

const LABEL: Record<string, string> = { login: 'Logged in', failed: 'Failed login', logout: 'Logged out', register: 'Registered' };

export default async function AdminLogins() {
  const u = await requireUser(['admin']);
  const rows = await all<{ id: number; email: string; event: string; ip: string | null; user_agent: string | null; created_at: string; name: string | null; role: string | null }>(
    'SELECT l.*, u.name, u.role FROM login_activities l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 300');
  return (
    <DashShell user={u} active="/admin/logins" title="Login activity">
      <div className="card table-wrap">
        {rows.length === 0 ? <p className="muted">No activity yet.</p> : (
          <table className="data"><thead><tr><th>Date</th><th>User</th><th>Event</th><th>IP address</th><th>Device</th></tr></thead><tbody>
            {rows.map(r => <tr key={r.id}><td className="small">{r.created_at}</td><td>{r.name ?? '—'}<div className="small muted">{r.email}{r.role ? ` · ${r.role}` : ''}</div></td>
              <td><span className={`status ${r.event === 'failed' ? 'failed' : r.event === 'register' ? 'quoted' : 'approved'}`}>{LABEL[r.event]}</span></td><td className="small">{r.ip ?? '—'}</td><td className="small muted">{(r.user_agent ?? '').slice(0, 70)}</td></tr>)}
          </tbody></table>)}
      </div>
    </DashShell>
  );
}
