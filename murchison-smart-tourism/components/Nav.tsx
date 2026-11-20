'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState } from 'react';

const LINKS = [
  { href: '/plan', label: 'Smart itinerary' },
  { href: '/explore', label: '360° explore' },
  { href: '/navigate', label: 'Map & routes' },
  { href: '/budget', label: 'Budget' },
  { href: '/assistant', label: 'AI assistant' },
  { href: '/updates', label: 'Travel updates' },
];

export default function Nav() {
  const path = usePathname();
  const [open, setOpen] = useState(false);
  return (
    <header className="nav">
      <div className="container nav-inner">
        <Link href="/" className="brand" onClick={() => setOpen(false)}>
          <span className="brand-mark" aria-hidden>🌊</span>
          <span>Murchison Falls<small>Smart Tourism Platform</small></span>
        </Link>
        <button className="nav-toggle" aria-label="Menu" aria-expanded={open} onClick={() => setOpen(!open)}>☰</button>
        <nav className={`nav-links ${open ? 'open' : ''}`}>
          {LINKS.map(l => (
            <Link key={l.href} href={l.href} className={path.startsWith(l.href) ? 'active' : ''} onClick={() => setOpen(false)}>{l.label}</Link>
          ))}
        </nav>
      </div>
    </header>
  );
}
