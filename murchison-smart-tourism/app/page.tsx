import Link from 'next/link';
import { ACTIVITIES, GALLERY, PARK } from '@/lib/data';

const FEATURES = [
  { href: '/plan', icon: '🗓️', title: 'Smart itinerary', text: 'Personalised day-by-day travel schedules built from your interests, dates, fitness and budget.', main: true },
  { href: '/budget', icon: '💰', title: 'Travel budgeting', text: 'Estimated trip expenditure: park fees, activities, accommodation, transport and meals.' },
  { href: '/navigate', icon: '🧭', title: 'Smart navigation', text: 'Routes from Kampala and the locations of every attraction on an interactive map.' },
  { href: '/explore', icon: '👁️', title: 'Virtual exploration', text: '360° scenes and interactive hotspots, including tours built with H5P.' },
  { href: '/assistant', icon: '💬', title: 'AI travel assistant', text: 'Answers questions about the park and recommends activities.' },
  { href: '/updates', icon: '⛅', title: 'Travel updates', text: 'Live weather at Paraa, the best months to visit and important travel information.' },
];

export default function Home() {
  return (
    <>
      <section className="hero" style={{ backgroundImage: 'url(/images/hero.jpg)' }}>
        <div className="container">
          <div className="eyebrow">Uganda Wildlife Authority · {PARK.name}</div>
          <h1>Plan your perfect trip to Murchison Falls</h1>
          <p>Tell us what you love, when you are coming and how much you want to spend. We will build your day-by-day itinerary, estimate the cost, map the route and let you explore the park before you travel.</p>
          <div className="btn-row" style={{ marginTop: 20 }}>
            <Link href="/plan" className="btn btn-primary">Plan my trip</Link>
            <Link href="/explore" className="btn btn-light">Explore in 360°</Link>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <h2>Everything you need to plan your visit</h2>
          <p className="muted">One platform for Murchison Falls National Park, Uganda&apos;s largest national park.</p>
          <div className="grid grid-2" style={{ marginTop: 16 }}>
            {FEATURES.map(f => (
              <Link key={f.href} href={f.href} className={`card feature ${f.main ? 'main' : ''}`}>
                <span className="icon" aria-hidden>{f.icon}</span>
                <span>
                  <h3>{f.title}{f.main && <span className="badge">Start here</span>}</h3>
                  <p>{f.text}</p>
                </span>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="section" style={{ background: '#fff' }}>
        <div className="container">
          <h2>How it works</h2>
          <ol className="steps" style={{ marginTop: 16 }}>
            <li><strong>Explore virtually</strong><p className="muted small">Look around the falls, the Nile and the savannah in 360°.</p></li>
            <li><strong>Choose your interests</strong><p className="muted small">Big game, boat trips, chimpanzees, the shoebill, hiking…</p></li>
            <li><strong>Get your itinerary</strong><p className="muted small">A timed schedule for each day, with reasons for every choice.</p></li>
            <li><strong>Check cost, route and weather</strong><p className="muted small">Budget in UGX and US$, the route on a map, and the forecast.</p></li>
          </ol>
        </div>
      </section>

      <section className="section">
        <div className="container grid grid-2" style={{ alignItems: 'center' }}>
          <div>
            <h2>About the park</h2>
            <p>{PARK.summary}</p>
            <p className="muted small">Area: about {PARK.areaKm2.toLocaleString()} km² · Managed by the {PARK.manager}</p>
            <h3 style={{ marginTop: 16 }}>Top experiences</h3>
            <div className="choices">
              {ACTIVITIES.filter(a => a.priority >= 6).map(a => <span key={a.id} className="chip">{a.name}</span>)}
            </div>
          </div>
          <div className="grid grid-2">
            {GALLERY.slice(0, 2).map(g => (
              <figure key={g.src} style={{ margin: 0 }}>
                <div className="photo"><img src={g.src} alt={g.caption} /></div>
                <figcaption className="photo-caption">{g.caption}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <div className="card" style={{ background: 'var(--green)', color: '#fff', display: 'flex', gap: 16, alignItems: 'center', justifyContent: 'space-between', flexWrap: 'wrap' }}>
            <div>
              <h2 style={{ marginBottom: 4 }}>Ready to plan?</h2>
              <p style={{ margin: 0, opacity: .9 }}>It takes less than a minute to get a personalised itinerary and budget.</p>
            </div>
            <Link href="/plan" className="btn btn-primary">Build my itinerary</Link>
          </div>
        </div>
      </section>
    </>
  );
}
