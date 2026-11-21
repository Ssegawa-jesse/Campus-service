'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState } from 'react';
import { logoutAction } from '@/app/actions/auth';

const LINKS = [
  { href: '/plan', label: 'Smart itinerary' },
  { href: '/budget', label: 'Budget' },
  { href: '/navigate', label: 'Map & routes' },
  { href: '/explore', label: '360° explore' },
  { href: '/operators', label: 'Tour operators' },
  { href: '/assistant', label: 'AI assistant' },
  { href: '/updates', label: 'Updates' },
];

export type NavUser = { name: string; role: 'admin' | 'tourist' | 'operator' } | null;

export default function Nav({ user }: { user: NavUser }) {
  const path = usePathname();
  const [open, setOpen] = useState(false);
  const dash = user ? (user.role === 'admin' ? '/admin' : user.role === 'operator' ? '/operator' : '/tourist') : null;
  return (
    <header className="nav">
      <div className="container nav-inner">
        <Link href="/" className="brand" onClick={() => setOpen(false)}>
          <img src="/images/mubs-logo.svg" alt="MUBS logo" className="brand-logo" width={40} height={40} />
          <span>Murchison Falls<small>Smart Tourism Platform</small></span>
        </Link>
        <button className="nav-toggle" aria-label="Menu" aria-expanded={open} onClick={() => setOpen(!open)}>☰</button>
        <nav className={`nav-links ${open ? 'open' : ''}`}>
          {LINKS.map(l => (
            <Link key={l.href} href={l.href} className={path.startsWith(l.href) ? 'active' : ''} onClick={() => setOpen(false)}>{l.label}</Link>
          ))}
          {user && dash ? (
            <>
              <Link href={dash} className={`nav-cta ${path.startsWith(dash) ? 'active' : ''}`} onClick={() => setOpen(false)}>My dashboard</Link>
              <form action={logoutAction}><button className="nav-logout" type="submit">Log out</button></form>
            </>
          ) : (
            <>
              <Link href="/login" className={path === '/login' ? 'active' : ''} onClick={() => setOpen(false)}>Log in</Link>
              <Link href="/register" className="nav-cta" onClick={() => setOpen(false)}>Register</Link>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}
