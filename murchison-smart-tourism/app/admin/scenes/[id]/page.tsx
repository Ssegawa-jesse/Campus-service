import type { Metadata } from 'next';
import Link from 'next/link';
import { notFound } from 'next/navigation';
import DashShell from '@/components/DashShell';
import { EditSceneForm, HotspotEditor } from '@/components/forms/AdminForms';
import { requireUser } from '@/lib/auth';
import { all, one, type Hotspot, type Scene } from '@/lib/db';
import { ACTIVITIES } from '@/lib/data';
import { blobEnabled } from '@/lib/storage';
import { deleteHotspotAction, deleteSceneAction, togglePublishAction } from '@/app/actions/admin';

export const metadata: Metadata = { title: 'Edit 360° scene' };

export default async function EditScene({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const u = await requireUser(['admin']);
  const scene = await one<Scene>('SELECT * FROM scenes WHERE id = ?', [Number(id)]);
  if (!scene) notFound();
  const hotspots = await all<Hotspot>('SELECT * FROM hotspots WHERE scene_id = ? ORDER BY id', [scene.id]);
  const others = await all<{ id: number; title: string }>('SELECT id, title FROM scenes WHERE id != ? ORDER BY sort, id', [scene.id]);
  const acts = ACTIVITIES.map(a => ({ id: a.id, name: a.name }));
  return (
    <DashShell user={u} active="/admin/scenes" title={scene.title}>
      <div className="btn-row" style={{ marginBottom: 12, alignItems: 'center' }}>
        <Link href="/admin/scenes" className="btn btn-ghost btn-sm">← All scenes</Link>
        <form action={togglePublishAction}><input type="hidden" name="id" value={scene.id} />
          <button className={`btn btn-sm ${scene.published ? 'btn-ghost' : 'btn-primary'}`}>{scene.published ? 'Unpublish' : 'Publish on the website'}</button></form>
        {scene.published ? <Link href={`/explore?scene=${scene.id}`} className="btn btn-ghost btn-sm">View on the website</Link> : <span className="status pending">draft</span>}
      </div>
      <HotspotEditor sceneId={scene.id} image={scene.image_url} hotspots={hotspots} otherScenes={others} />
      <div className="grid grid-2" style={{ marginTop: 16, alignItems: 'start' }}>
        <div className="card">
          <h3>Hotspots on this scene</h3>
          {hotspots.length === 0 ? <p className="muted small">No hotspots yet.</p> : hotspots.map(h => (
            <div key={h.id} style={{ display: 'flex', justifyContent: 'space-between', gap: 8, borderBottom: '1px solid var(--line)', padding: '8px 0' }}>
              <div className="small"><strong>{h.type === 'info' ? 'ⓘ' : '➜'} {h.text}</strong><div className="muted">
                {h.type === 'scene' ? `Leads to: ${others.find(o => o.id === h.target_scene_id)?.title ?? '(deleted scene)'} · ` : ''}pitch {h.pitch}°, yaw {h.yaw}°</div></div>
              <form action={deleteHotspotAction}><input type="hidden" name="id" value={h.id} /><button className="btn btn-danger btn-sm">Delete</button></form>
            </div>
          ))}
        </div>
        <div>
          <EditSceneForm scene={scene} activities={acts} blob={blobEnabled()} />
          <form action={deleteSceneAction} className="card" style={{ marginTop: 16 }}>
            <input type="hidden" name="id" value={scene.id} />
            <h3>Delete scene</h3><p className="small muted">Removes the scene and its hotspots.</p>
            <button className="btn btn-danger btn-sm">Delete this scene</button>
          </form>
        </div>
      </div>
    </DashShell>
  );
}
