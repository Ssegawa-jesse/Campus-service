import type { Metadata } from 'next';
import Weather from '@/components/Weather';
import { MONTHS, TRAVEL_NOTICES } from '@/lib/data';

export const metadata: Metadata = { title: 'Travel updates' };

export default function UpdatesPage() {
  return (
    <>
      <section className="page-head">
        <div className="container">
          <div className="eyebrow">Travel updates</div>
          <h1>Weather and travel information</h1>
          <p>The live forecast at Paraa, the best months to visit and important information before you travel.</p>
        </div>
      </section>
      <section className="section">
        <div className="container">
          <h2>Weather this week</h2>
          <Weather />
        </div>
      </section>
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <h2>When to visit</h2>
          <p className="muted small">General pattern; individual years differ. <span className="chip orange">Dry</span> <span className="chip blue">Wet</span></p>
          <div className="months">
            {MONTHS.map(m => (
              <div key={m.m} className={m.season === 'Dry' ? 'dry' : 'wet'}>
                <strong>{m.m}</strong><div className="muted">{m.note}</div>
              </div>
            ))}
          </div>
        </div>
      </section>
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <h2>Before you travel</h2>
          <div className="grid grid-3">
            {TRAVEL_NOTICES.map(n => (
              <div className="card" key={n.title}><h3>{n.title}</h3><p className="small muted" style={{ margin: 0 }}>{n.body}</p></div>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
