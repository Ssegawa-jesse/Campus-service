'use client';
import { useEffect, useRef, useState } from 'react';
import { loadPannellum, type PannellumViewer } from '@/lib/pannellum-client';

export type ViewerScene = {
  id: string;
  title: string;
  image: string;
  description?: string | null;
  activity?: string | null;
  hotspots: { pitch: number; yaw: number; type: 'info' | 'scene'; text: string; sceneId?: string }[];
};

export default function PanoViewer({ scenes, initial }: { scenes: ViewerScene[]; initial?: string }) {
  const el = useRef<HTMLDivElement>(null);
  const viewer = useRef<PannellumViewer | null>(null);
  const first = scenes.find(s => s.id === initial)?.id ?? scenes[0]?.id;
  const [scene, setScene] = useState(first);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!scenes.length) return;
    let cancelled = false;
    loadPannellum().then(() => {
      if (cancelled || !el.current || !window.pannellum) return;
      const config = Object.fromEntries(scenes.map(s => [s.id, {
        title: s.title, type: 'equirectangular', panorama: s.image, autoRotate: -2,
        hotSpots: s.hotspots.map(h => ({ pitch: h.pitch, yaw: h.yaw, type: h.type, text: h.text, ...(h.sceneId ? { sceneId: h.sceneId } : {}) })),
      }]));
      const v = window.pannellum.viewer(el.current, {
        default: { firstScene: first, autoLoad: true, sceneFadeDuration: 800, showControls: true, compass: false }, scenes: config,
      });
      viewer.current = v;
      v.on('scenechange', (id: unknown) => setScene(String(id)));
    }).catch(e => setError(e.message));
    return () => { cancelled = true; viewer.current?.destroy(); viewer.current = null; };
  }, [scenes, first]);

  if (!scenes.length) return <div className="notice info">No 360° scenes have been published yet.</div>;
  const current = scenes.find(s => s.id === scene);
  return (
    <>
      <div className="scene-tabs">
        {scenes.map(s => (
          <button key={s.id} className={`btn btn-sm ${scene === s.id ? 'btn-dark' : 'btn-ghost'}`} onClick={() => { setScene(s.id); viewer.current?.loadScene(s.id); }}>{s.title}</button>
        ))}
      </div>
      {error ? <div className="notice warn">{error}</div> : <div className="pano"><div ref={el} style={{ width: '100%', height: '100%' }} aria-label="360° panorama viewer" /></div>}
      {current && (current.activity || current.description) && (
        <p className="small" style={{ marginTop: 8 }}>{current.activity && <span className="chip">{current.activity}</span>} {current.description}</p>
      )}
      <p className="muted small" style={{ marginTop: 6 }}>Drag to look around (or move your phone). Click the arrows to move between places and the ⓘ markers for information.</p>
    </>
  );
}
