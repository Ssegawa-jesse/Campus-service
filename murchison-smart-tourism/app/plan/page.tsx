import type { Metadata } from 'next';
import { Suspense } from 'react';
import Planner from '@/components/Planner';

export const metadata: Metadata = { title: 'Smart itinerary' };

export default function PlanPage() {
  return (
    <>
      <section className="page-head">
        <div className="container">
          <div className="eyebrow">Smart itinerary</div>
          <h1>Your personalised Murchison Falls trip</h1>
          <p>Choose your dates, interests and budget. The planner matches the park&apos;s activities to you, times them for the best wildlife viewing and estimates the cost.</p>
        </div>
      </section>
      <section className="section">
        <div className="container">
          <Suspense fallback={<p className="muted">Loading the planner…</p>}>
            <Planner />
          </Suspense>
        </div>
      </section>
    </>
  );
}
