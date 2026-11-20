import type { Metadata } from 'next';
import { Suspense } from 'react';
import ParkMap from '@/components/ParkMap';

export const metadata: Metadata = { title: 'Map and routes' };

export default function NavigatePage() {
  return (
    <>
      <section className="page-head">
        <div className="container">
          <div className="eyebrow">Smart navigation</div>
          <h1>Routes and attraction locations</h1>
          <p>See where every attraction, gate and stop is, how far it is from Paraa, and get directions from where you are.</p>
        </div>
      </section>
      <section className="section">
        <div className="container">
          <Suspense fallback={<p className="muted">Loading the map…</p>}><ParkMap /></Suspense>
        </div>
      </section>
    </>
  );
}
