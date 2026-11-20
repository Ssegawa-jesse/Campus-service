'use client';
import { useEffect, useRef, useState } from 'react';
import { SCENES } from '@/lib/data';

type PannellumViewer = { loadScene: (id: string) => void; getScene: () => string; on: (e: string, f: (id: string) => void) => void; destroy: () => void };
declare global { interface Window { pannellum?: { viewer: (el: HTMLElement, cfg: unknown) => PannellumViewer } } }

function loadPannellum(): Promise<void> {
  if (window.pannellum) return Promise.resolve();
  return new Promise((resolve, reject) => {
    if (!document.querySelector('link[data-pannellum]')) {
      const css = document.createElement('link');
      css.rel = 'stylesheet'; css.href = '/vendor/pannellum/pannellum.css'; css.dataset.pannellum = '1';
      document.head.appendChild(css);
    }
    const existing = document.querySelector<HTMLScriptElement>('script[data-pannellum]');
    const s = existing ?? document.createElement('script');
    s.addEventListener('load', () => resolve());
    s.addEventListener('error', () => reject(new Error('Could not load the 360° viewer')));
    if (!existing) { s.src = '/vendor/pannellum/pannellum.js'; s.dataset.pannellum = '1'; document.body.appendChild(s); }
  });
}

export default function PanoViewer() {
  const el = useRef<HTMLDivElement>(null);
  const viewer = useRef<PannellumViewer | null>(null);
  const [scene, setScene] = useState(SCENES[0].id);
  const [error, setError] = useState('');

  useEffect(() => {
    let cancelled = false;
    loadPannellum().then(() => {
      if (cancelled || !el.current || !window.pannellum) return;
      const scenes = Object.fromEntries(SCENES.map(s => [s.id, {
        title: s.title, type: 'equirectangular', panorama: s.image, autoRotate: -2,
        hotSpots: s.hotspots.map(h => ({ pitch: h.pitch, yaw: h.yaw, type: h.type, text: h.text, ...(h.sceneId ? { sceneId: h.sceneId } : {}) })),
      }]));
      viewer.current = window.pannellum.viewer(el.current, {
        default: { firstScene: SCENES[0].id, autoLoad: true, sceneFadeDuration: 800, showControls: true, compass: false }, scenes,
      });
      viewer.current.on('scenechange', (id: string) => setScene(id));
    }).catch(e => setError(e.message));
    return () => { cancelled = true; viewer.current?.destroy(); viewer.current = null; };
  }, []);

  return (
    <>
      <div className="scene-tabs">
        {SCENES.map(s => (
          <button key={s.id} className={`btn ${scene === s.id ? 'btn-dark' : 'btn-ghost'}`} onClick={() => { setScene(s.id); viewer.current?.loadScene(s.id); }}>{s.title}</button>
        ))}
      </div>
      {error ? <div className="notice warn">{error}</div> : <div className="pano"><div ref={el} style={{ width: '100%', height: '100%' }} aria-label="360° panorama viewer" /></div>}
      <p className="muted small" style={{ marginTop: 8 }}>Drag to look around (or move your phone). Click the arrows to move between places and the ⓘ markers for information.</p>
    </>
  );
}
