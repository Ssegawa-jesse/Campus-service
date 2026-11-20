'use client';
import { useMemo, useState } from 'react';
import { ACTIVITIES, ORIGINS, Origin, PARK_FEES, RESIDENCY_LABELS, Residency, TIERS, TRANSPORT, Tier, Transport, UGX_PER_USD } from '@/lib/data';
import { activityCost, computeBudget, feeUgx } from '@/lib/budget';
import { formatUgx, formatUsd } from '@/lib/planner';

export default function BudgetCalculator() {
  const [days, setDays] = useState(3);
  const [adults, setAdults] = useState(2);
  const [children, setChildren] = useState(0);
  const [residency, setResidency] = useState<Residency>('fnr');
  const [tier, setTier] = useState<Tier>('mid');
  const [transport, setTransport] = useState<Transport>('tour');
  const [origin, setOrigin] = useState<Origin>('kampala');
  const [ids, setIds] = useState<string[]>(['launch', 'game-drive', 'top-falls']);

  const toggle = (id: string) => setIds(ids.includes(id) ? ids.filter(x => x !== id) : [...ids, id]);
  const budget = useMemo(() => computeBudget({ days, adults, children, residency, tier, transport, origin, activityIds: ids }),
    [days, adults, children, residency, tier, transport, origin, ids]);
  const persons = Math.max(1, adults + children);
  const max = Math.max(...budget.lines.map(l => l.ugx), 1);

  return (
    <div className="grid grid-2" style={{ alignItems: 'start' }}>
      <div className="card">
        <h2 style={{ fontSize: '1.2rem' }}>Your trip</h2>
        <div className="row-3">
          <div className="field"><label htmlFor="b-days">Days</label>
            <input id="b-days" type="number" min={1} max={14} value={days} onChange={e => setDays(Math.max(1, Number(e.target.value)))} /></div>
          <div className="field"><label htmlFor="b-ad">Adults</label>
            <input id="b-ad" type="number" min={1} max={30} value={adults} onChange={e => setAdults(Math.max(1, Number(e.target.value)))} /></div>
          <div className="field"><label htmlFor="b-ch">Children</label>
            <input id="b-ch" type="number" min={0} max={30} value={children} onChange={e => setChildren(Math.max(0, Number(e.target.value)))} /></div>
        </div>
        <div className="field"><label htmlFor="b-res">Visitor category</label>
          <select id="b-res" value={residency} onChange={e => setResidency(e.target.value as Residency)}>
            {Object.entries(RESIDENCY_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select></div>
        <div className="row">
          <div className="field"><label htmlFor="b-tier">Accommodation</label>
            <select id="b-tier" value={tier} onChange={e => setTier(e.target.value as Tier)}>
              {(Object.keys(TIERS) as Tier[]).map(t => <option key={t} value={t}>{TIERS[t].label} (≈US${TIERS[t].perPersonNightUsd}/person/night)</option>)}
            </select></div>
          <div className="field"><label htmlFor="b-origin">Starting from</label>
            <select id="b-origin" value={origin} onChange={e => setOrigin(e.target.value as Origin)}>
              {Object.entries(ORIGINS).map(([k, v]) => <option key={k} value={k}>{v.label} ({v.kmToGate} km to the gate)</option>)}
            </select></div>
        </div>
        <div className="field"><label htmlFor="b-tr">Transport</label>
          <select id="b-tr" value={transport} onChange={e => setTransport(e.target.value as Transport)}>
            {Object.entries(TRANSPORT).map(([k, v]) => <option key={k} value={k}>{v.label}</option>)}
          </select>
          <p className="muted small" style={{ marginTop: 4 }}>{TRANSPORT[transport].note}</p></div>
        <div className="field"><label>Activities</label>
          {ACTIVITIES.map(a => (
            <label key={a.id} style={{ display: 'flex', gap: 10, alignItems: 'center', fontWeight: 400, padding: '6px 0', borderBottom: '1px solid var(--line)' }}>
              <input type="checkbox" checked={ids.includes(a.id)} onChange={() => toggle(a.id)} />
              <span style={{ flex: 1 }}>{a.name}</span>
              <span className="muted small" style={{ whiteSpace: 'nowrap' }}>{formatUsd(activityCost(a.id, adults, children, residency))}</span>
            </label>
          ))}
        </div>
      </div>

      <div className="card" style={{ position: 'sticky', top: 76 }}>
        <h2 style={{ fontSize: '1.2rem' }}>Estimated expenditure</h2>
        <div className="summary-bar" style={{ gridTemplateColumns: '1fr 1fr' }}>
          <div className="card"><span className="muted small">Total</span><strong>{formatUsd(budget.total)}</strong><span className="small muted">{formatUgx(budget.total)}</span></div>
          <div className="card"><span className="muted small">Per person</span><strong>{formatUsd(budget.total / persons)}</strong><span className="small muted">{formatUgx(budget.total / persons)}</span></div>
        </div>
        <table className="costs">
          <tbody>
            {budget.lines.map(l => (
              <tr key={l.label}>
                <td><strong>{l.label}</strong><div className="muted small">{l.detail}</div>
                  <div style={{ height: 6, borderRadius: 3, background: 'var(--green-soft)', marginTop: 4 }}>
                    <div style={{ height: 6, borderRadius: 3, width: `${(l.ugx / max) * 100}%`, background: 'var(--green-2)' }} /></div></td>
                <td className="num">{formatUsd(l.ugx)}<div className="muted small">{formatUgx(l.ugx)}</div></td>
              </tr>
            ))}
            <tr className="total"><td>Total</td><td className="num">{formatUsd(budget.total)}</td></tr>
          </tbody>
        </table>
        <p className="muted small" style={{ marginTop: 10 }}>
          Park entry is {residency === 'eac' ? formatUgx(PARK_FEES.entryPerDay.eac) : `US$${PARK_FEES.entryPerDay[residency]}`} per adult per day
          ({formatUgx(feeUgx(PARK_FEES.entryPerDay, residency))}). Rate used: US$1 = UGX {UGX_PER_USD.toLocaleString()}.
          All figures are planning estimates; confirm current UWA tariffs and lodge prices before paying.
        </p>
      </div>
    </div>
  );
}
