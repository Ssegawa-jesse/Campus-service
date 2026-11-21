import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell from '@/components/DashShell';
import OperatorGate from '@/components/OperatorGate';
import { PackageForm } from '@/components/forms/BookingForms';
import { operatorFor, requireUser } from '@/lib/auth';
import { all, type Package } from '@/lib/db';
import { ACTIVITIES } from '@/lib/data';
import { togglePackageAction } from '@/app/actions/bookings';

export const metadata: Metadata = { title: 'My packages' };

export default async function OperatorPackages({ searchParams }: { searchParams: Promise<{ edit?: string }> }) {
  const { edit } = await searchParams;
  const u = await requireUser(['operator']);
  const op = await operatorFor(u.id);
  const pkgs = await all<Package>('SELECT * FROM packages WHERE operator_id = ? ORDER BY id DESC', [op?.id ?? 0]);
  const editing = pkgs.find(p => p.id === Number(edit));
  const acts = ACTIVITIES.map(a => ({ id: a.id, name: a.name }));
  return (
    <DashShell user={u} active="/operator/packages" title="My packages">
      <OperatorGate op={op} />
      <div className="grid grid-2" style={{ alignItems: 'start' }}>
        <PackageForm key={editing?.id ?? 'new'} activities={acts} pkg={editing} />
        <div className="card">
          <h3>Packages</h3>
          {pkgs.length === 0 ? <p className="muted">No packages yet.</p> : pkgs.map(p => (
            <div key={p.id} style={{ borderBottom: '1px solid var(--line)', padding: '10px 0' }}>
              <strong>{p.title}</strong> {p.is_active ? <span className="status approved">active</span> : <span className="status">hidden</span>}
              <div className="small muted">{p.days} days · UGX {p.price_ugx.toLocaleString()} per person</div>
              <div className="btn-row" style={{ marginTop: 6 }}>
                <Link className="btn btn-ghost btn-sm" href={`/operator/packages?edit=${p.id}`}>Edit</Link>
                <form action={togglePackageAction} className="inline-form"><input type="hidden" name="id" value={p.id} /><button className="btn btn-ghost btn-sm">{p.is_active ? 'Hide' : 'Show'}</button></form>
              </div>
            </div>
          ))}
        </div>
      </div>
    </DashShell>
  );
}
