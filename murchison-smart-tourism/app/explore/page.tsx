import type { Metadata } from 'next';
import Link from 'next/link';
import PanoViewer from '@/components/PanoViewer';
import { GALLERY, H5P_TOURS } from '@/lib/data';

export const metadata: Metadata = { title: '360° virtual exploration' };

export default function ExplorePage() {
  return (
    <>
      <section className="page-head">
        <div className="container">
          <div className="eyebrow">Virtual exploration</div>
          <h1>Explore the park before you travel</h1>
          <p>Look around the Top of the Falls, the Victoria Nile at Paraa and the northern savannah in 360°, then add the places you like to your itinerary.</p>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="card">
            <PanoViewer />
            <div className="btn-row" style={{ marginTop: 12 }}>
              <Link href="/plan" className="btn btn-primary">Plan a trip to these places</Link>
            </div>
          </div>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <h2>H5P interactive tours</h2>
          {H5P_TOURS.length > 0 ? (
            <div className="grid">
              {H5P_TOURS.map(t => (
                <div className="card" key={t.src}>
                  <h3>{t.title}</h3>
                  <p className="muted small">{t.description}</p>
                  <iframe className="h5p-frame" src={t.src} title={t.title} allowFullScreen allow="fullscreen; gyroscope; accelerometer" />
                </div>
              ))}
            </div>
          ) : (
            <div className="card">
              <p>Tours made with H5P&apos;s <strong>Virtual Tour (360)</strong> content type will appear here.</p>
              <ol className="small" style={{ paddingLeft: 18, margin: 0 }}>
                <li>Take 360° photos in the park with a 360° camera and export them as 2:1 JPG images.</li>
                <li>In H5P (for example the free Lumi desktop app), create a Virtual Tour (360), add the photos as scenes and place hotspots.</li>
                <li>Export the tour as one HTML file into <code>public/h5p/</code>, or copy the embed link from h5p.com.</li>
                <li>Add the tour to <code>H5P_TOURS</code> in <code>lib/data.ts</code>.</li>
              </ol>
            </div>
          )}
        </div>
      </section>

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
