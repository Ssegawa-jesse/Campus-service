import type { Metadata } from 'next';
import Link from 'next/link';
import PanoViewer, { type ViewerScene } from '@/components/PanoViewer';
import { all, type Hotspot, type Scene } from '@/lib/db';
import { ACTIVITIES, GALLERY, H5P_TOURS, SCENES } from '@/lib/data';

export const metadata: Metadata = { title: '360° virtual exploration' };
export const dynamic = 'force-dynamic';

/** Scenes uploaded by administrators; the built-in sample scenes are shown until the first one is published. */
async function loadScenes(): Promise<{ scenes: ViewerScene[]; samples: boolean }> {
  const rows = await all<Scene>('SELECT * FROM scenes WHERE published = 1 ORDER BY sort, id').catch(() => []);
  if (!rows.length) {
    return { samples: true, scenes: SCENES.map(s => ({ id: s.id, title: s.title, image: s.image, hotspots: s.hotspots })) };
  }
  const ids = new Set(rows.map(r => r.id));
  const hs = await all<Hotspot>(`SELECT * FROM hotspots WHERE scene_id IN (${rows.map(() => '?').join(',')})`, rows.map(r => r.id));
  return {
    samples: false,
    scenes: rows.map(r => ({
      id: String(r.id), title: r.title, image: r.image_url, description: r.description,
      activity: ACTIVITIES.find(a => a.id === r.activity_id)?.name ?? null,
      hotspots: hs.filter(h => h.scene_id === r.id && (h.type === 'info' || (h.target_scene_id && ids.has(h.target_scene_id))))
        .map(h => ({ pitch: h.pitch, yaw: h.yaw, type: h.type, text: h.text, ...(h.type === 'scene' ? { sceneId: String(h.target_scene_id) } : {}) })),
    })),
  };
}

export default async function ExplorePage({ searchParams }: { searchParams: Promise<{ scene?: string }> }) {
  const { scene } = await searchParams;
  const { scenes, samples } = await loadScenes();
  return (
    <>
      <section className="page-head">
        <div className="container">
          <div className="eyebrow">Virtual exploration</div>
          <h1>See the activities before you travel</h1>
          <p>Look around the places where Murchison Falls activities happen in 360°, then add the ones you like to your itinerary.</p>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="card">
            {samples && <div className="notice info">Sample scenes are shown. Administrators replace them by uploading 360° photos under Admin → 360° scenes &amp; hotspots.</div>}
            <PanoViewer scenes={scenes} initial={scene} />
            <div className="btn-row" style={{ marginTop: 12 }}>
              <Link href="/plan" className="btn btn-primary">Plan a trip to these places</Link>
            </div>
          </div>
        </div>
      </section>

      {H5P_TOURS.length > 0 && (
        <section className="section" style={{ paddingTop: 0 }}>
          <div className="container">
            <h2>H5P interactive tours</h2>
            <div className="grid">
              {H5P_TOURS.map(t => (
                <div className="card" key={t.src}>
                  <h3>{t.title}</h3>
                  <p className="muted small">{t.description}</p>
                  <iframe className="h5p-frame" src={t.src} title={t.title} allowFullScreen allow="fullscreen; gyroscope; accelerometer" />
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <h2>Photo gallery</h2>
          <div className="grid grid-3">
            {GALLERY.map(g => (
              <figure key={g.src} style={{ margin: 0 }}>
                <div className="photo"><img src={g.src} alt={g.caption} loading="lazy" /></div>
                <figcaption className="photo-caption">{g.caption}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
