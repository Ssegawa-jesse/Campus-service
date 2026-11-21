'use client';

export type PannellumViewer = {
  loadScene: (id: string) => void; getScene: () => string; on: (e: string, f: (...a: unknown[]) => void) => void; destroy: () => void;
  mouseEventToCoords: (e: MouseEvent) => [number, number]; getPitch: () => number; getYaw: () => number;
};
declare global { interface Window { pannellum?: { viewer: (el: HTMLElement, cfg: unknown) => PannellumViewer } } }

/** Loads the Pannellum 360° viewer (bundled in public/vendor) once. */
export function loadPannellum(): Promise<void> {
  if (window.pannellum) return Promise.resolve();
  return new Promise((resolve, reject) => {
    if (!document.querySelector('link[data-pannellum]')) {
      const css = document.createElement('link');
      css.rel = 'stylesheet'; css.href = '/vendor/pannellum/pannellum.css'; css.dataset.pannellum = '1';
      document.head.appendChild(css);
    }
    const existing = document.querySelector<HTMLScriptElement>('script[data-pannellum]');
    if (existing && window.pannellum) return resolve();
    const s = existing ?? document.createElement('script');
    s.addEventListener('load', () => resolve());
    s.addEventListener('error', () => reject(new Error('Could not load the 360° viewer')));
    if (!existing) { s.src = '/vendor/pannellum/pannellum.js'; s.dataset.pannellum = '1'; document.body.appendChild(s); }
  });
}
