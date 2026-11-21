import Link from 'next/link';
import type { User } from '@/lib/db';

const MENUS: Record<User['role'], { href: string; label: string }[]> = {
  admin: [
    { href: '/admin', label: 'Overview' }, { href: '/admin/operators', label: 'Tour operators' }, { href: '/admin/bookings', label: 'Bookings' },
    { href: '/admin/payments', label: 'Payments' }, { href: '/admin/scenes', label: '360° scenes & hotspots' }, { href: '/admin/users', label: 'Users & admins' },
    { href: '/admin/sms', label: 'SMS log' }, { href: '/admin/logins', label: 'Login activity' }, { href: '/account', label: 'My account' },
  ],
  operator: [
    { href: '/operator', label: 'Overview' }, { href: '/operator/bookings', label: 'Bookings' }, { href: '/operator/packages', label: 'My packages' },
    { href: '/operator/profile', label: 'Company profile' }, { href: '/account', label: 'My account' },
  ],
  tourist: [
    { href: '/tourist', label: 'My bookings' }, { href: '/plan', label: 'Plan a trip' }, { href: '/operators', label: 'Book an operator' }, { href: '/account', label: 'My account' },
  ],
};

export default function DashShell({ user, active, title, children }: { user: User; active: string; title: string; children: React.ReactNode }) {
  return (
    <section className="section">
      <div className="container dash">
        <aside className="dash-side">
          <div className="who"><strong>{user.name}</strong><div className="small muted" style={{ textTransform: 'capitalize' }}>{user.role === 'operator' ? 'Tour operator' : user.role}</div></div>
          {MENUS[user.role].map(m => <Link key={m.href} href={m.href} className={active === m.href ? 'on' : ''}>{m.label}</Link>)}
        </aside>
        <div>
          <h1 style={{ fontSize: '1.6rem' }}>{title}</h1>
          {children}
        </div>
      </div>
    </section>
  );
}

export function StatusBadge({ s }: { s: string }) {
  return <span className={`status ${s}`}>{s}</span>;
}
