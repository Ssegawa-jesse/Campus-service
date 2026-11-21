import type { Metadata } from 'next';
import Link from 'next/link';
import DashShell from '@/components/DashShell';
import { NewSceneForm } from '@/components/forms/AdminForms';
import { requireUser } from '@/lib/auth';
import { all, type Scene } from '@/lib/db';
import { ACTIVITIES } from '@/lib/data';
import { blobEnabled } from '@/lib/storage';

export const metadata: Metadata = { title: '360° scenes & hotspots' };

export default async function AdminScenes() {
  const u = await requireUser(['admin']);
  const scenes = await all<Scene & { hs: number }>('SELECT s.*, (SELECT COUNT(*) FROM hotspots h WHERE h.scene_id = s.id) AS hs FROM scenes s ORDER BY s.sort, s.id');
  const acts = ACTIVITIES.map(a => ({ id: a.id, name: a.name }));
  const covered = new Set(scenes.filter(s => s.published).map(s => s.activity_id));
  const onVercelWithoutBlob = !!process.env.VERCEL && !blobEnabled();
  return (
    <DashShell user={u} active="/admin/scenes" title="360° scenes & hotspots">
      <p className="muted">Upload a 360° photo for each activity at Murchison Falls, place hotspots on it, then publish it. Published scenes replace the sample scenes on the 360° explore page and are linked from the smart itinerary.</p>
      {onVercelWithoutBlob && <div className="notice warn">To upload photos on Vercel, connect a Blob store: Vercel → your project → Storage → Create → Blob, then redeploy.</div>}
      <div className="grid grid-2" style={{ alignItems: 'start' }}>
        <NewSceneForm activities={acts} blob={blobEnabled()} />
        <div className="card">
          <h3>Activities with a published 360° scene</h3>
          <ul className="clean small">{ACTIVITIES.map(a => <li key={a.id}>{covered.has(a.id) ? '✅' : '⬜'} {a.name}</li>)}</ul>
        </div>
      </div>
      <div className="grid grid-3" style={{ marginTop: 16 }}>
        {scenes.map(s => (
          <Link key={s.id} href={`/admin/scenes/${s.id}`} className="card" style={{ textDecoration: 'none', color: 'inherit' }}>
            <img src={s.image_url} alt="" className="scene-thumb" loading="lazy" />
            <h3 style={{ marginTop: 8 }}>{s.title}</h3>
            <div className="small muted">{ACTIVITIES.find(a => a.id === s.activity_id)?.name ?? 'General view'} · {s.hs} hotspot(s)</div>
            <div style={{ marginTop: 6 }}>{s.published ? <span className="status approved">published</span> : <span className="status pending">draft</span>}</div>
          </Link>
        ))}
      </div>
    </DashShell>
  );
}
