import type { Metadata } from 'next';
import Link from 'next/link';
import { redirect } from 'next/navigation';
import { all, type Operator, type Package } from '@/lib/db';
import { currentUser } from '@/lib/auth';
import { planSummary } from '@/lib/plancode';
import { BookingForm } from '@/components/forms/BookingForms';

export const metadata: Metadata = { title: 'Send an enquiry' };

export default async function BookPage({ searchParams }: { searchParams: Promise<{ operator?: string; package?: string; p?: string }> }) {
  const sp = await searchParams;
  const u = await currentUser();
  const here = `/book?${new URLSearchParams(Object.entries(sp).filter(([, v]) => v) as [string, string][]).toString()}`;
  if (!u) redirect(`/login?next=${encodeURIComponent(here)}`);
  if (u.role !== 'tourist') {
    return <section className="section"><div className="container auth-wrap"><div className="card">
      <h2>Bookings are made from a tourist account</h2><p className="muted">You are logged in as {u.role === 'admin' ? 'an administrator' : 'a tour operator'}. Log out and log in (or register) as a tourist to send an enquiry.</p>
    </div></div></section>;
  }
  const ops = await all<Operator>("SELECT * FROM operators WHERE status = 'approved' ORDER BY company");
  const pkgs = await all<Package>('SELECT * FROM packages WHERE is_active = 1');
  const summary = planSummary(sp.p);
  const pkg = pkgs.find(p => p.id === Number(sp.package));
  const inTwoWeeks = new Date(Date.now() + 14 * 864e5).toISOString().slice(0, 10);
  return (
    <>
      <section className="page-head"><div className="container">
        <div className="eyebrow">Booking enquiry</div>
        <h1>Book your Murchison Falls trip</h1>
        <p>Your enquiry goes to the operator by SMS. They will send you a quote, which you can pay by MTN Mobile Money, Airtel Money or card.</p>
      </div></section>
      <section className="section"><div className="container" style={{ maxWidth: 760 }}>
        {ops.length === 0 ? <div className="card empty"><h2>No approved operators yet</h2><p>Please check again soon.</p><Link href="/plan">Back to the planner</Link></div> : (
          <BookingForm
            operators={ops.map(o => ({ id: o.id, company: o.company, packages: pkgs.filter(p => p.operator_id === o.id).map(p => ({ id: p.id, title: p.title, days: p.days, price_ugx: p.price_ugx })) }))}
            defaults={{
              operatorId: Number(sp.operator) || undefined, packageId: pkg?.id, plan: summary ? sp.p : undefined, planSummary: summary?.text,
              start: summary?.input.startDate && summary.input.startDate >= new Date().toISOString().slice(0, 10) ? summary.input.startDate : inTwoWeeks,
              days: summary?.input.days ?? pkg?.days ?? 3, adults: summary?.input.adults ?? 2, children: summary?.input.children ?? 0,
            }} />
        )}
      </div></section>
    </>
  );
}
