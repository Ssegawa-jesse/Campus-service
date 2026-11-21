'use client';
import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
import { useEffect, useMemo, useState } from 'react';
import { ACTIVITIES, ATTRACTIONS, INTERESTS, Interest, ORIGINS, Origin, RESIDENCY_LABELS, Residency, TIERS, TRANSPORT, Tier, Transport } from '@/lib/data';
import { buildPlan, DEFAULT_INPUT, formatUgx, formatUsd, Plan, PlanInput } from '@/lib/planner';
import { DayWeather, describe, fetchForecast } from '@/lib/weather';

const STORE_KEY = 'mf-saved-plans';

function encode(input: PlanInput) {
  return btoa(unescape(encodeURIComponent(JSON.stringify(input))));
}
function decode(s: string): PlanInput | null {
  try { return { ...DEFAULT_INPUT, ...JSON.parse(decodeURIComponent(escape(atob(s)))) }; } catch { return null; }
}
function inTwoWeeks() {
  const d = new Date(); d.setDate(d.getDate() + 14); return d.toISOString().slice(0, 10);
}

type Saved = { id: string; name: string; savedAt: string; input: PlanInput };

export default function Planner() {
  const params = useSearchParams();
  const router = useRouter();
  const [input, setInput] = useState<PlanInput>({ ...DEFAULT_INPUT });
  const [plan, setPlan] = useState<Plan | null>(null);
  const [weather, setWeather] = useState<DayWeather[] | null>(null);
  const [saved, setSaved] = useState<Saved[]>([]);
  const [message, setMessage] = useState('');
  const [sceneFor, setSceneFor] = useState<Record<string, number>>({});

  // Load a shared plan from the link, and any saved plans from this browser.
  useEffect(() => {
    const shared = params.get('p');
    const fromLink = shared ? decode(shared) : null;
    const start = { ...DEFAULT_INPUT, startDate: inTwoWeeks(), ...(fromLink ?? {}) };
    setInput(start);
    if (fromLink) setPlan(buildPlan(start));
    try { setSaved(JSON.parse(localStorage.getItem(STORE_KEY) || '[]')); } catch { /* storage unavailable */ }
    fetchForecast().then(setWeather).catch(() => setWeather([]));
    fetch('/api/scenes').then(r => r.json()).then((rows: { id: number; activity_id: string | null }[]) => {
      const m: Record<string, number> = {};
      rows.forEach(r => { if (r.activity_id && !m[r.activity_id]) m[r.activity_id] = r.id; });
      setSceneFor(m);
    }).catch(() => {});
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const set = <K extends keyof PlanInput>(k: K, v: PlanInput[K]) => setInput(prev => ({ ...prev, [k]: v }));
  const toggleInterest = (i: Interest) => set('interests', input.interests.includes(i) ? input.interests.filter(x => x !== i) : [...input.interests, i]);
  const toggleMust = (id: string) => set('mustDo', input.mustDo.includes(id) ? input.mustDo.filter(x => x !== id) : [...input.mustDo, id]);

  function generate(e?: React.FormEvent) {
    e?.preventDefault();
    if (input.interests.length === 0 && input.mustDo.length === 0) { setMessage('Choose at least one interest.'); return; }
    setMessage('');
    const p = buildPlan(input);
    setPlan(p);
    router.replace(`/plan?p=${encode(input)}`, { scroll: false });
    setTimeout(() => document.getElementById('itinerary')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 50);
  }

  function save() {
    if (!plan) return;
    const name = `${plan.input.days}-day trip from ${new Date(plan.input.startDate + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}`;
    const next = [{ id: String(Date.now()), name, savedAt: new Date().toISOString(), input: plan.input }, ...saved].slice(0, 10);
    setSaved(next);
    try { localStorage.setItem(STORE_KEY, JSON.stringify(next)); setMessage('Itinerary saved on this device.'); } catch { setMessage('Could not save on this device.'); }
  }
  function load(s: Saved) { setInput(s.input); setPlan(buildPlan(s.input)); router.replace(`/plan?p=${encode(s.input)}`, { scroll: false }); }
  function remove(id: string) {
    const next = saved.filter(s => s.id !== id); setSaved(next);
    try { localStorage.setItem(STORE_KEY, JSON.stringify(next)); } catch { /* ignore */ }
  }
  async function share() {
    const url = window.location.href;
    try {
      if (navigator.share) await navigator.share({ title: 'My Murchison Falls itinerary', url });
      else { await navigator.clipboard.writeText(url); setMessage('Link copied. Anyone with the link will see this itinerary.'); }
    } catch { /* cancelled */ }
  }

  const weatherByDate = useMemo(() => Object.fromEntries((weather ?? []).map(w => [w.date, w])), [weather]);
  const mapIds = plan ? Array.from(new Set(plan.days.flatMap(d => d.items.map(i => i.attractionId).filter(Boolean)))) : [];

  return (
    <div className="planner">
      <form className="card form" onSubmit={generate}>
        <h2 style={{ fontSize: '1.2rem' }}>Trip details</h2>
        <div className="row">
          <div className="field"><label htmlFor="start">Arrival date</label>
            <input id="start" type="date" value={input.startDate} onChange={e => set('startDate', e.target.value)} required /></div>
          <div className="field"><label htmlFor="days">Number of days</label>
            <select id="days" value={input.days} onChange={e => set('days', Number(e.target.value))}>
              {[1, 2, 3, 4, 5, 6].map(d => <option key={d} value={d}>{d} day{d > 1 ? 's' : ''}{d > 1 ? ` (${d - 1} night${d > 2 ? 's' : ''})` : ' (day trip)'}</option>)}
            </select></div>
        </div>
        <div className="row">
          <div className="field"><label htmlFor="adults">Adults</label>
            <input id="adults" type="number" min={1} max={20} value={input.adults} onChange={e => set('adults', Number(e.target.value))} /></div>
          <div className="field"><label htmlFor="children">Children (under 15)</label>
            <input id="children" type="number" min={0} max={20} value={input.children} onChange={e => set('children', Number(e.target.value))} /></div>
        </div>
        <div className="field"><label htmlFor="res">Visitor category (for park fees)</label>
          <select id="res" value={input.residency} onChange={e => set('residency', e.target.value as Residency)}>
            {Object.entries(RESIDENCY_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select></div>
        <div className="field"><label htmlFor="origin">Starting from</label>
          <select id="origin" value={input.origin} onChange={e => set('origin', e.target.value as Origin)}>
            {Object.entries(ORIGINS).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
          </select></div>

        <div className="field"><label>What interests you?</label>
          <div className="choices">
            {INTERESTS.map(i => (
              <label key={i.id} className={`choice ${input.interests.includes(i.id) ? 'on' : ''}`}>
                <input type="checkbox" checked={input.interests.includes(i.id)} onChange={() => toggleInterest(i.id)} />
                <span aria-hidden>{i.icon}</span>{i.label}
              </label>
            ))}
          </div></div>

        <div className="field"><label>Fitness level</label>
          <div className="segmented">
            {([[1, 'Easy only'], [2, 'Moderate'], [3, 'Very active']] as const).map(([v, l]) => (
              <button type="button" key={v} className={input.fitness === v ? 'on' : ''} onClick={() => set('fitness', v)}>{l}</button>))}
          </div></div>
        <div className="field"><label>Budget level</label>
          <div className="segmented">
            {(Object.keys(TIERS) as Tier[]).map(t => (
              <button type="button" key={t} className={input.tier === t ? 'on' : ''} onClick={() => set('tier', t)}>{TIERS[t].label}</button>))}
          </div></div>
        <div className="field"><label>Pace</label>
          <div className="segmented">
            <button type="button" className={input.pace === 'relaxed' ? 'on' : ''} onClick={() => set('pace', 'relaxed')}>Relaxed (2 a day)</button>
            <button type="button" className={input.pace === 'full' ? 'on' : ''} onClick={() => set('pace', 'full')}>Full (up to 3)</button>
          </div></div>
        <div className="field"><label htmlFor="transport">Transport</label>
          <select id="transport" value={input.transport} onChange={e => set('transport', e.target.value as Transport)}>
            {Object.entries(TRANSPORT).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
          </select></div>

        <details className="field">
          <summary style={{ cursor: 'pointer', fontWeight: 600, fontSize: '.9rem' }}>Must-do activities (optional)</summary>
          <div className="choices" style={{ marginTop: 8 }}>
            {ACTIVITIES.map(a => (
              <label key={a.id} className={`choice ${input.mustDo.includes(a.id) ? 'on' : ''}`} style={{ fontSize: '.82rem' }}>
                <input type="checkbox" checked={input.mustDo.includes(a.id)} onChange={() => toggleMust(a.id)} />{a.name}
              </label>
            ))}
          </div>
        </details>

        {message && <div className="notice info">{message}</div>}
        <button className="btn btn-primary" style={{ width: '100%' }}>Build my itinerary</button>

        {saved.length > 0 && (
          <div style={{ marginTop: 18 }}>
            <label>Saved itineraries</label>
            {saved.map(s => (
              <div key={s.id} style={{ display: 'flex', justifyContent: 'space-between', gap: 8, alignItems: 'center', fontSize: '.88rem', padding: '6px 0', borderBottom: '1px solid var(--line)' }}>
                <button type="button" onClick={() => load(s)} style={{ background: 'none', border: 0, padding: 0, color: 'var(--nile)', cursor: 'pointer', textAlign: 'left', font: 'inherit' }}>{s.name}</button>
                <button type="button" onClick={() => remove(s.id)} aria-label="Delete" style={{ background: 'none', border: 0, cursor: 'pointer', color: 'var(--muted)' }}>✕</button>
              </div>
            ))}
          </div>
        )}
      </form>

      <div id="itinerary">
        {!plan ? (
          <div className="card empty">
            <div style={{ fontSize: 44 }}>🗺️</div>
            <h2>Your itinerary will appear here</h2>
            <p>Fill in your trip details and press <strong>Build my itinerary</strong>.</p>
          </div>
        ) : (
          <PlanView plan={plan} weatherByDate={weatherByDate} mapIds={mapIds as string[]} onSave={save} onShare={share} sceneFor={sceneFor} bookHref={`/book?p=${encode(plan.input)}`} />
        )}
      </div>
    </div>
  );
}

function PlanView({ plan, weatherByDate, mapIds, onSave, onShare, sceneFor, bookHref }: {
  plan: Plan; weatherByDate: Record<string, DayWeather>; mapIds: string[]; onSave: () => void; onShare: () => void;
  sceneFor: Record<string, number>; bookHref: string;
}) {
  const activityCount = plan.days.flatMap(d => d.items).filter(i => i.kind === 'activity').length;
  const persons = plan.input.adults + plan.input.children;
  return (
    <>
      <div className="summary-bar">
        <div className="card"><span className="muted small">Days</span><strong>{plan.input.days}</strong></div>
        <div className="card"><span className="muted small">Activities</span><strong>{activityCount}</strong></div>
        <div className="card"><span className="muted small">Estimated total</span><strong>{formatUsd(plan.totalUgx)}</strong><span className="small muted">{formatUgx(plan.totalUgx)}</span></div>
        <div className="card"><span className="muted small">Per person</span><strong>{formatUsd(plan.totalUgx / persons)}</strong><span className="small muted">{plan.season.m}: {plan.season.season.toLowerCase()} season</span></div>
      </div>

      <div className="btn-row no-print" style={{ marginBottom: 16 }}>
        <Link className="btn btn-primary" href={bookHref}>📩 Book this itinerary</Link>
        <button className="btn btn-dark" onClick={onSave}>💾 Save</button>
        <button className="btn btn-ghost" onClick={onShare}>🔗 Share</button>
        <button className="btn btn-ghost" onClick={() => window.print()}>🖨️ Print / PDF</button>
        <Link className="btn btn-ghost" href={`/navigate?ids=${mapIds.join(',')}`}>🧭 Show on map</Link>
      </div>

      {plan.warnings.map((w, i) => <div key={i} className="notice warn">⚠️ {w}</div>)}

      {plan.days.map(day => {
        const w = weatherByDate[day.date];
        const wd = w && describe(w.code);
        return (
          <div className="card day" key={day.day}>
            <h3>
              <span>Day {day.day}: {day.title}</span>
              {w && wd ? <span className="chip blue" title="Forecast for Paraa">{wd.icon} {w.min}–{w.max}°C · {w.rain}% rain</span>
                : <span className="chip">{plan.season.season} season</span>}
            </h3>
            <ul className="timeline">
              {day.items.map((it, i) => (
                <li key={i} className={it.kind}>
                  {it.kind === 'tip' ? <div className="tip-box">💡 {it.detail}</div> : (
                    <>
                      {it.costUgx ? <span className="cost">{formatUsd(it.costUgx)}</span> : null}
                      <div><span className="time">{it.time}</span><strong>{it.title}</strong></div>
                      <div className="muted small">{it.detail}</div>
                      {it.activityId && sceneFor[it.activityId] ? <Link className="small no-print" href={`/explore?scene=${sceneFor[it.activityId]}`}>👁️ View in 360°</Link> : null}
                    </>
                  )}
                </li>
              ))}
            </ul>
          </div>
        );
      })}

      <div className="grid grid-2">
        <div className="card">
          <h3>Estimated budget</h3>
          <table className="costs">
            <tbody>
              {plan.costs.map(c => (
                <tr key={c.label}><td><strong>{c.label}</strong><div className="muted small">{c.detail}</div></td>
                  <td className="num">{formatUsd(c.ugx)}<div className="muted small">{formatUgx(c.ugx)}</div></td></tr>
              ))}
              <tr className="total"><td>Total</td><td className="num">{formatUsd(plan.totalUgx)}<div className="small">{formatUgx(plan.totalUgx)}</div></td></tr>
            </tbody>
          </table>
          <p className="muted small" style={{ marginTop: 8 }}>Planning estimate. Confirm current UWA fees and lodge rates before booking.</p>
          <Link href="/budget" className="small">Adjust in the budget calculator →</Link>
        </div>
        <div className="card">
          <h3>Why this plan</h3>
          <ul className="clean small">{plan.reasons.map((r, i) => <li key={i}>{r}</li>)}</ul>
          {plan.skipped.length > 0 && (<>
            <h3 style={{ marginTop: 14 }}>Not included</h3>
            <ul className="clean small muted">{plan.skipped.map((r, i) => <li key={i}>{r}</li>)}</ul>
          </>)}
          <h3 style={{ marginTop: 14 }}>What to pack</h3>
          <ul className="clean small">{plan.packing.map((r, i) => <li key={i}>{r}</li>)}</ul>
        </div>
      </div>
      <p className="muted small" style={{ marginTop: 12 }}>Places in this plan: {mapIds.map(id => ATTRACTIONS.find(a => a.id === id)?.name).join(', ')}.</p>
    </>
  );
}
