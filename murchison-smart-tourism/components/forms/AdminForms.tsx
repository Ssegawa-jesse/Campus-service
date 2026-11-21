'use client';
import { useActionState, useEffect, useRef, useState } from 'react';
import { upload } from '@vercel/blob/client';
import { addHotspotAction, createAdminAction, createSceneAction, testSmsAction, updateSceneAction } from '@/app/actions/admin';
import { loadPannellum, type PannellumViewer } from '@/lib/pannellum-client';
import { Notice } from './AuthForms';

export function CreateAdminForm() {
  const [state, action, pending] = useActionState(createAdminAction, {});
  return (
    <form action={action} className="card">
      <h3>Add another administrator</h3>
      <Notice state={state} />
      <div className="row">
        <div className="field"><label htmlFor="an">Full name</label><input id="an" name="name" required /></div>
        <div className="field"><label htmlFor="ae">Email</label><input id="ae" name="email" type="text" inputMode="email" required /></div>
      </div>
      <div className="row">
        <div className="field"><label htmlFor="ap">Phone (for SMS alerts)</label><input id="ap" name="phone" placeholder="0772 123 456" /></div>
        <div className="field"><label htmlFor="apw">Temporary password</label><input id="apw" name="password" type="password" autoComplete="new-password" minLength={8} required /></div>
      </div>
      <button className="btn btn-dark" disabled={pending}>Add administrator</button>
    </form>
  );
}

export function TestSmsForm() {
  const [state, action, pending] = useActionState(testSmsAction, {});
  return (
    <form action={action} className="card">
      <h3>Send an SMS</h3>
      <Notice state={state} />
      <div className="row">
        <div className="field"><label htmlFor="sp">Phone</label><input id="sp" name="phone" placeholder="0772 123 456" required /></div>
        <div className="field"><label htmlFor="sm">Message</label><input id="sm" name="message" defaultValue="Test message from the Murchison Falls Smart Tourism Platform." required /></div>
      </div>
      <button className="btn btn-dark" disabled={pending}>Send</button>
    </form>
  );
}

/** Uploads a 360° photo to Vercel Blob (online) or the local uploads folder (on your computer) and returns its address. */
function ImageUpload({ blob, onUploaded }: { blob: boolean; onUploaded: (url: string) => void }) {
  const [status, setStatus] = useState('');
  const [preview, setPreview] = useState('');
  async function pick(file: File | undefined) {
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { setStatus('Choose a JPG, PNG or WebP image.'); return; }
    setStatus('Uploading…');
    try {
      let url: string;
      if (blob) {
        const res = await upload(`scenes/${file.name.toLowerCase().replace(/[^a-z0-9.]+/g, '-')}`, file, {
          access: 'public', handleUploadUrl: '/api/admin/blob', multipart: file.size > 8 * 1024 * 1024,
          onUploadProgress: p => setStatus(`Uploading… ${Math.round(p.percentage)}%`),
        });
        url = res.url;
      } else {
        const fd = new FormData(); fd.append('file', file);
        const res = await fetch('/api/admin/upload', { method: 'POST', body: fd });
        const j = await res.json();
        if (!res.ok) throw new Error(j.error || 'Upload failed');
        url = j.url;
      }
      setPreview(url); onUploaded(url);
      setStatus('Uploaded ✓');
      const img = new Image();
      img.onload = () => { if (Math.abs(img.naturalWidth / img.naturalHeight - 2) > 0.05) setStatus('Uploaded ✓ — note: this photo is not 2:1. Use a 360° (equirectangular) photo for a full view.'); };
      img.src = url;
    } catch (e) { setStatus(e instanceof Error ? e.message : 'Upload failed'); }
  }
  return (
    <div className="field">
      <label htmlFor="file">360° photo (JPG, about 4096 × 2048)</label>
      <input id="file" type="file" accept="image/jpeg,image/png,image/webp" onChange={e => pick(e.target.files?.[0])} />
      {status && <p className="small" style={{ marginTop: 6 }}>{status}</p>}
      {preview && <img src={preview} alt="" className="scene-thumb" style={{ marginTop: 6 }} />}
    </div>
  );
}

type ActivityOpt = { id: string; name: string };

export function NewSceneForm({ activities, blob }: { activities: ActivityOpt[]; blob: boolean }) {
  const [state, action, pending] = useActionState(createSceneAction, {});
  const [url, setUrl] = useState('');
  return (
    <form action={action} className="card">
      <h3>Add a 360° scene</h3>
      <Notice state={state} />
      <ImageUpload blob={blob} onUploaded={setUrl} />
      <input type="hidden" name="image_url" value={url} />
      <div className="field"><label htmlFor="st">Title</label><input id="st" name="title" placeholder="Launch trip – hippos near Paraa" required /></div>
      <div className="field"><label htmlFor="sa">Activity shown in this scene</label>
        <select id="sa" name="activity_id" defaultValue=""><option value="">General view of the park</option>
          {activities.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}</select></div>
      <div className="field"><label htmlFor="sd">Short description</label><textarea id="sd" name="description" rows={2} /></div>
      <button className="btn btn-primary" disabled={pending || !url}>{pending ? 'Saving…' : 'Save and add hotspots'}</button>
    </form>
  );
}

export function EditSceneForm({ scene, activities, blob }: { scene: { id: number; title: string; activity_id: string | null; description: string | null; sort: number }; activities: ActivityOpt[]; blob: boolean }) {
  const [state, action, pending] = useActionState(updateSceneAction, {});
  const [url, setUrl] = useState('');
  return (
    <form action={action} className="card">
      <h3>Scene details</h3>
      <Notice state={state} />
      <input type="hidden" name="id" value={scene.id} />
      <input type="hidden" name="image_url" value={url} />
      <div className="field"><label htmlFor="et">Title</label><input id="et" name="title" defaultValue={scene.title} required /></div>
      <div className="row">
        <div className="field"><label htmlFor="ea">Activity</label>
          <select id="ea" name="activity_id" defaultValue={scene.activity_id ?? ''}><option value="">General view of the park</option>
            {activities.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}</select></div>
        <div className="field"><label htmlFor="es">Order</label><input id="es" name="sort" type="number" defaultValue={scene.sort} /></div>
      </div>
      <div className="field"><label htmlFor="ed">Description</label><textarea id="ed" name="description" rows={2} defaultValue={scene.description ?? ''} /></div>
      <details className="field"><summary style={{ cursor: 'pointer', fontWeight: 600, fontSize: '.9rem' }}>Replace the photo</summary><ImageUpload blob={blob} onUploaded={setUrl} /></details>
      <button className="btn btn-dark" disabled={pending}>Save scene</button>
    </form>
  );
}

type HS = { id: number; pitch: number; yaw: number; type: 'info' | 'scene'; text: string; target_scene_id: number | null };

/** Shows the scene; clicking inside the panorama picks where a new hotspot goes. */
export function HotspotEditor({ sceneId, image, hotspots, otherScenes }: { sceneId: number; image: string; hotspots: HS[]; otherScenes: { id: number; title: string }[] }) {
  const el = useRef<HTMLDivElement>(null);
  const viewer = useRef<PannellumViewer | null>(null);
  const [pos, setPos] = useState<{ pitch: number; yaw: number } | null>(null);
  const [type, setType] = useState<'info' | 'scene'>('info');
  const [state, action, pending] = useActionState(addHotspotAction, {});
  const hsKey = hotspots.map(h => h.id).join(',');

  useEffect(() => {
    let cancelled = false;
    loadPannellum().then(() => {
      if (cancelled || !el.current || !window.pannellum) return;
      viewer.current?.destroy();
      const v = window.pannellum.viewer(el.current, {
        type: 'equirectangular', panorama: image, autoLoad: true, showControls: true,
        hotSpots: hotspots.map(h => ({ pitch: h.pitch, yaw: h.yaw, type: h.type === 'scene' ? 'scene' : 'info', text: h.text, ...(h.type === 'scene' ? { sceneId: 'x' } : {}) })),
      });
      viewer.current = v;
      // A click (not a drag) inside the panorama chooses the hotspot position.
      let down: [number, number] | null = null;
      el.current.addEventListener('mousedown', e => { down = [e.clientX, e.clientY]; });
      el.current.addEventListener('mouseup', e => {
        if (!down || Math.hypot(e.clientX - down[0], e.clientY - down[1]) > 5) return;
        const [pitch, yaw] = v.mouseEventToCoords(e);
        setPos({ pitch: Math.round(pitch * 10) / 10, yaw: Math.round(yaw * 10) / 10 });
      });
    });
    return () => { cancelled = true; viewer.current?.destroy(); viewer.current = null; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [image, hsKey]);

  return (
    <div className="card">
      <h3>Hotspots</h3>
      <p className="small muted">Drag to look around, then <strong>click</strong> on the spot where the hotspot should appear.</p>
      <div className="pano-editor"><div ref={el} style={{ width: '100%', height: '100%' }} /></div>
      <form action={action} style={{ marginTop: 12 }}>
        <Notice state={state} />
        <input type="hidden" name="scene_id" value={sceneId} />
        <input type="hidden" name="pitch" value={pos?.pitch ?? ''} />
        <input type="hidden" name="yaw" value={pos?.yaw ?? ''} />
        <input type="hidden" name="type" value={type} />
        <p className="small">{pos ? <>📍 Position chosen: up/down {pos.pitch}°, left/right {pos.yaw}°</> : 'No position chosen yet — click inside the photo.'}</p>
        <div className="field"><label>Hotspot type</label>
          <div className="segmented">
            <button type="button" className={type === 'info' ? 'on' : ''} onClick={() => setType('info')}>ⓘ Information</button>
            <button type="button" className={type === 'scene' ? 'on' : ''} onClick={() => setType('scene')} disabled={!otherScenes.length}>➜ Go to another scene</button>
          </div></div>
        {type === 'scene' && <div className="field"><label htmlFor="tg">Leads to</label>
          <select id="tg" name="target_scene_id" required>{otherScenes.map(s => <option key={s.id} value={s.id}>{s.title}</option>)}</select></div>}
        <div className="field"><label htmlFor="ht">{type === 'info' ? 'Information text' : 'Arrow label'}</label>
          <input id="ht" name="text" placeholder={type === 'info' ? 'Hippos gather here in the afternoon' : 'Go to the Top of the Falls'} required /></div>
        <button className="btn btn-primary" disabled={pending || !pos}>Add hotspot</button>
      </form>
    </div>
  );
}
