'use client';
import 'leaflet/dist/leaflet.css';
import { useSearchParams } from 'next/navigation';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { Map as LeafletMap, CircleMarker } from 'leaflet';
import { ATTRACTIONS, Attraction, ORIGINS, PARK } from '@/lib/data';

const COLORS: Record<Attraction['category'], string> = {
  Waterfall: '#1d5c7a', River: '#2b8fb8', Wildlife: '#d9822b', Forest: '#2e6b52', Gate: '#7a4d9c', 'On the way': '#a33a2c', Town: '#5d6b64',
};

// Approximate road route from Kampala to Paraa through Masindi and the southern gate (for orientation only).
const ROUTE: [number, number][] = [
  [0.3476, 32.5825], [0.849, 32.473], [1.31, 32.46], [1.47, 32.07], [1.6744, 31.715], [1.97, 31.77], [2.2786, 31.6857], [2.2843, 31.5655],
];

function km(a: { lat: number; lng: number }, b: { lat: number; lng: number }) {
  const R = 6371, toR = (d: number) => (d * Math.PI) / 180;
  const dLat = toR(b.lat - a.lat), dLng = toR(b.lng - a.lng);
  const h = Math.sin(dLat / 2) ** 2 + Math.cos(toR(a.lat)) * Math.cos(toR(b.lat)) * Math.sin(dLng / 2) ** 2;
  return Math.round(2 * R * Math.asin(Math.sqrt(h)));
}

export default function ParkMap() {
  const params = useSearchParams();
  const highlight = useMemo(() => (params.get('ids') || '').split(',').filter(Boolean), [params]);
  const [filter, setFilter] = useState<string>('All');
  const [active, setActive] = useState<string | null>(highlight[0] ?? null);
  const mapRef = useRef<LeafletMap | null>(null);
  const markers = useRef<Record<string, CircleMarker>>({});
  const el = useRef<HTMLDivElement>(null);
  const paraa = ATTRACTIONS.find(a => a.id === 'paraa')!;

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const L = (await import('leaflet')).default;
      if (cancelled || !el.current || mapRef.current) return;
      const map = L.map(el.current, { scrollWheelZoom: true }).setView([PARK.centre.lat, PARK.centre.lng], 9);
      mapRef.current = map;
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      }).addTo(map);
      L.polyline(ROUTE, { color: '#d9822b', weight: 4, dashArray: '8 6', opacity: .9 }).addTo(map)
        .bindTooltip('Approximate route from Kampala via Masindi');
      L.circleMarker([0.3476, 32.5825], { radius: 7, color: '#fff', weight: 2, fillColor: '#1d2521', fillOpacity: 1 }).addTo(map).bindPopup('<strong>Kampala</strong><br/>Start of the journey');
      for (const a of ATTRACTIONS) {
        const on = highlight.includes(a.id);
        const m = L.circleMarker([a.lat, a.lng], { radius: on ? 11 : 8, color: '#fff', weight: 2, fillColor: COLORS[a.category], fillOpacity: .95 })
          .addTo(map)
          .bindPopup(`<strong>${a.name}</strong><br/><em>${a.category}</em><br/>${a.description}<br/>` +
            `<a target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=${a.lat},${a.lng}">Get directions ↗</a>`);
        m.on('click', () => setActive(a.id));
        markers.current[a.id] = m;
      }
      const focus = highlight.length ? ATTRACTIONS.filter(a => highlight.includes(a.id)) : ATTRACTIONS;
      map.fitBounds(L.latLngBounds(focus.map(a => [a.lat, a.lng] as [number, number])).pad(0.25));
    })();
    return () => { cancelled = true; mapRef.current?.remove(); mapRef.current = null; };
  }, [highlight]);

  function focusOn(a: Attraction) {
    setActive(a.id);
    const map = mapRef.current;
    if (!map) return;
    map.setView([a.lat, a.lng], 12, { animate: true });
    markers.current[a.id]?.openPopup();
  }

  const categories = ['All', ...Array.from(new Set(ATTRACTIONS.map(a => a.category)))];
  const list = ATTRACTIONS.filter(a => filter === 'All' || a.category === filter);

  return (
    <>
      {highlight.length > 0 && <div className="notice info">Showing the {highlight.length} places in your itinerary (larger markers).</div>}
      <div className="map-wrap">
        <div>
          <div className="field">
            <label htmlFor="cat">Show</label>
            <select id="cat" value={filter} onChange={e => setFilter(e.target.value)}>{categories.map(c => <option key={c}>{c}</option>)}</select>
          </div>
          <div className="place-list">
            {list.map(a => (
              <div key={a.id} className={`place ${active === a.id ? 'on' : ''}`} onClick={() => focusOn(a)}>
                <h4><span style={{ color: COLORS[a.category] }}>●</span> {a.name} {highlight.includes(a.id) && <span className="chip orange">In your plan</span>}</h4>
                <div className="muted small">{a.category} · about {km(paraa, a)} km from Paraa (straight line)</div>
                {active === a.id && (<>
                  <p className="small" style={{ margin: '6px 0' }}>{a.description}</p>
                  {a.tip && <p className="small" style={{ margin: '0 0 6px' }}>💡 {a.tip}</p>}
                  <a className="small" target="_blank" rel="noopener" href={`https://www.google.com/maps/dir/?api=1&destination=${a.lat},${a.lng}`}>Get directions in Google Maps ↗</a>
                </>)}
              </div>
            ))}
          </div>
        </div>
        <div>
          <div ref={el} className="map" role="region" aria-label="Map of Murchison Falls National Park" />
          <div className="legend">
            {Object.entries(COLORS).map(([k, c]) => <span key={k} style={{ ['--c' as string]: c }}>{k}</span>)}
            <span style={{ ['--c' as string]: '#d9822b' }}>- - Route from Kampala</span>
          </div>
        </div>
      </div>

      <div className="grid grid-3" style={{ marginTop: 20 }}>
        {Object.values(ORIGINS).map(o => (
          <div className="card" key={o.label}>
            <h3>From {o.label}</h3>
            <p className="small" style={{ margin: 0 }}>About <strong>{o.kmToGate} km</strong> to Kichumbanyobo Gate ({o.hoursToGate} hours), then about 45 km inside the park to Paraa.</p>
          </div>
        ))}
      </div>
      <p className="muted small" style={{ marginTop: 10 }}>Map positions and the route are approximate and for planning only. Use a navigation app and follow park signs and ranger instructions on the ground.</p>
    </>
  );
}
