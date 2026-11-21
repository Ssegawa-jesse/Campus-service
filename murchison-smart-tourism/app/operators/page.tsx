import type { Metadata } from 'next';
import Link from 'next/link';
import { all, type Operator, type Package } from '@/lib/db';
import { ACTIVITIES } from '@/lib/data';

export const metadata: Metadata = { title: 'Tour operators' };
export const dynamic = 'force-dynamic';

export default async function OperatorsPage() {
  const ops = await all<Operator>("SELECT * FROM operators WHERE status = 'approved' ORDER BY company");
  const pkgs = await all<Package>('SELECT p.* FROM packages p JOIN operators o ON o.id = p.operator_id WHERE p.is_active = 1 AND o.status = \'approved\' ORDER BY p.price_ugx');
  const actName = (id: string) => ACTIVITIES.find(a => a.id === id)?.name ?? id;
  return (
    <>
      <section className="page-head"><div className="container">
        <div className="eyebrow">Tour operators</div>
        <h1>Book an approved tour operator</h1>
        <p>Every operator here has been checked and approved by the platform administrators. Send an enquiry, receive a quote by SMS and pay by Mobile Money or card.</p>
      </div></section>
      <section className="section"><div className="container">
        {ops.length === 0 ? (
          <div className="card empty"><h2>No approved operators yet</h2><p>Tour operators can <Link href="/register?role=operator">register here</Link>; they appear once an administrator approves them.</p></div>
        ) : (
          <div className="grid">
            {ops.map(o => (
              <div className="card" key={o.id}>
                <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
                  <div><h2 style={{ fontSize: '1.25rem', marginBottom: 2 }}>{o.company} <span className="status approved">UWA-area approved</span></h2>
                    {o.licence_no && <div className="small muted">Licence: {o.licence_no}</div>}</div>
                  <Link className="btn btn-primary" href={`/book?operator=${o.id}`}>Send an enquiry</Link>
                </div>
                {o.description && <p className="muted" style={{ marginTop: 8 }}>{o.description}</p>}
                <div className="grid grid-3" style={{ marginTop: 10 }}>
                  {pkgs.filter(p => p.operator_id === o.id).map(p => (
                    <div key={p.id} className="card" style={{ boxShadow: 'none', background: 'var(--sand)' }}>
                      <h3>{p.title}</h3>
                      <div className="small muted">{p.days} days · UGX {p.price_ugx.toLocaleString()} per person</div>
                      {p.activities && <div className="small" style={{ margin: '6px 0' }}>{p.activities.split(',').filter(Boolean).map(actName).join(' · ')}</div>}
                      {p.description && <p className="small muted">{p.description}</p>}
                      <Link className="btn btn-ghost btn-sm" href={`/book?operator=${o.id}&package=${p.id}`}>Book this package</Link>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}
      </div></section>
    </>
  );
}
