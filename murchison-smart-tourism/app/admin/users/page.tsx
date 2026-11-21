import type { Metadata } from 'next';
import DashShell from '@/components/DashShell';
import { CreateAdminForm } from '@/components/forms/AdminForms';
import { requireUser } from '@/lib/auth';
import { all, type User } from '@/lib/db';
import { toggleUserAction } from '@/app/actions/admin';

export const metadata: Metadata = { title: 'Users & admins' };

export default async function AdminUsers() {
  const me = await requireUser(['admin']);
  const users = await all<User & { last_login: string | null }>(
    "SELECT u.id, u.name, u.email, u.phone, u.role, u.is_active, u.created_at, (SELECT MAX(created_at) FROM login_activities l WHERE l.user_id = u.id AND l.event = 'login') AS last_login FROM users u ORDER BY CASE u.role WHEN 'admin' THEN 0 WHEN 'operator' THEN 1 ELSE 2 END, u.id DESC");
  return (
    <DashShell user={me} active="/admin/users" title="Users & administrators">
      <CreateAdminForm />
      <div className="card table-wrap" style={{ marginTop: 16 }}>
        <table className="data"><thead><tr><th>Name</th><th>Role</th><th>Contact</th><th>Registered</th><th>Last login</th><th>Account</th></tr></thead><tbody>
          {users.map(x => (
            <tr key={x.id}>
              <td><strong>{x.name}</strong></td><td style={{ textTransform: 'capitalize' }}>{x.role === 'operator' ? 'Tour operator' : x.role}</td>
              <td className="small">{x.email}<br />{x.phone}</td><td className="small">{x.created_at.slice(0, 10)}</td><td className="small">{x.last_login ?? '—'}</td>
              <td>{x.id === me.id ? <span className="small muted">You</span> : (
                <form action={toggleUserAction}><input type="hidden" name="id" value={x.id} />
                  <button className={`btn btn-sm ${x.is_active ? 'btn-danger' : 'btn-ghost'}`}>{x.is_active ? 'Deactivate' : 'Activate'}</button></form>)}</td>
            </tr>
          ))}
        </tbody></table>
      </div>
    </DashShell>
  );
}
