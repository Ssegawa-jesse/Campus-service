'use client';
import { useActionState, useState } from 'react';
import { createBookingAction, operatorProfileAction, payAction, quoteAction, savePackageAction } from '@/app/actions/bookings';
import { Notice } from './AuthForms';

type Op = { id: number; company: string; packages: { id: number; title: string; days: number; price_ugx: number }[] };

export function BookingForm({ operators, defaults }: {
  operators: Op[];
  defaults: { operatorId?: number; packageId?: number; plan?: string; start: string; days: number; adults: number; children: number; planSummary?: string };
}) {
  const [state, action, pending] = useActionState(createBookingAction, {});
  const [opId, setOpId] = useState(defaults.operatorId ?? operators[0]?.id ?? 0);
  const op = operators.find(o => o.id === opId);
  return (
    <form action={action} className="card">
      <Notice state={state} />
      <input type="hidden" name="plan" value={defaults.plan ?? ''} />
      {defaults.planSummary && <div className="notice info">📋 Your smart itinerary will be sent with this enquiry: {defaults.planSummary}</div>}
      <div className="field"><label htmlFor="op">Tour operator</label>
        <select id="op" name="operator_id" value={opId} onChange={e => setOpId(Number(e.target.value))} required>
          {operators.map(o => <option key={o.id} value={o.id}>{o.company}</option>)}
        </select></div>
      <div className="field"><label htmlFor="pkg">Package (optional)</label>
        <select id="pkg" name="package_id" defaultValue={defaults.packageId ?? ''} key={opId}>
          <option value="">{defaults.plan ? 'No package – follow my itinerary' : 'No package – custom trip'}</option>
          {op?.packages.map(p => <option key={p.id} value={p.id}>{p.title} ({p.days} days, UGX {p.price_ugx.toLocaleString()} per person)</option>)}
        </select></div>
      <div className="row">
        <div className="field"><label htmlFor="start">Arrival date</label><input id="start" name="start_date" type="date" defaultValue={defaults.start} required /></div>
        <div className="field"><label htmlFor="days">Days</label><input id="days" name="days" type="number" min={1} max={14} defaultValue={defaults.days} required /></div>
      </div>
      <div className="row">
        <div className="field"><label htmlFor="adults">Adults</label><input id="adults" name="adults" type="number" min={1} max={30} defaultValue={defaults.adults} required /></div>
        <div className="field"><label htmlFor="children">Children</label><input id="children" name="children" type="number" min={0} max={30} defaultValue={defaults.children} /></div>
      </div>
      <div className="field"><label htmlFor="msg">Message to the operator (optional)</label><textarea id="msg" name="message" rows={3} placeholder="Accommodation preferences, pick-up point, special requests…" /></div>
      <button className="btn btn-primary" disabled={pending || !operators.length}>{pending ? 'Sending…' : 'Send enquiry'}</button>
    </form>
  );
}

export function PayForm({ reference, amount, live }: { reference: string; amount: string; live: boolean }) {
  const [state, action, pending] = useActionState(payAction, {});
  const [method, setMethod] = useState<'mtn' | 'airtel' | 'card'>('mtn');
  return (
    <form action={action} className="card">
      <h3>Pay {amount}</h3>
      <Notice state={state} />
      <input type="hidden" name="reference" value={reference} />
      <input type="hidden" name="method" value={method} />
      <div className="field"><label>Payment method</label>
        <div className="segmented">
          <button type="button" className={method === 'mtn' ? 'on' : ''} onClick={() => setMethod('mtn')}>MTN MoMo</button>
          <button type="button" className={method === 'airtel' ? 'on' : ''} onClick={() => setMethod('airtel')}>Airtel Money</button>
          <button type="button" className={method === 'card' ? 'on' : ''} onClick={() => setMethod('card')}>Card</button>
        </div></div>
      {method !== 'card' && <div className="field"><label htmlFor="phone">Mobile money number</label><input id="phone" name="phone" type="text" inputMode="tel" placeholder="0772 123 456" required /></div>}
      <button className="btn btn-primary" disabled={pending}>{pending ? 'Starting payment…' : `Pay ${amount}`}</button>
      <p className="small muted" style={{ marginTop: 8 }}>{live ? 'You will be taken to Flutterwave to approve the payment securely.' : 'Demo mode: payments are simulated until the Flutterwave key is added.'}</p>
    </form>
  );
}

export function QuoteForm({ reference, current, note, suggested }: { reference: string; current: number | null; note: string | null; suggested?: number }) {
  const [state, action, pending] = useActionState(quoteAction, {});
  return (
    <form action={action} className="card">
      <h3>{current ? 'Update quote' : 'Send a quote'}</h3>
      <Notice state={state} />
      <input type="hidden" name="reference" value={reference} />
      <div className="field"><label htmlFor="q">Total price for the group (UGX)</label>
        <input id="q" name="quote_ugx" type="number" min={10000} step={1000} defaultValue={current ?? suggested ?? ''} required />
        {suggested ? <p className="small muted" style={{ marginTop: 4 }}>The system&apos;s estimate for this trip is UGX {suggested.toLocaleString()}.</p> : null}</div>
      <div className="field"><label htmlFor="qn">What is included</label><textarea id="qn" name="quote_note" rows={3} defaultValue={note ?? ''} placeholder="Transport, accommodation, park fees, activities, meals…" /></div>
      <button className="btn btn-primary" disabled={pending}>{pending ? 'Sending…' : 'Send quote by SMS'}</button>
    </form>
  );
}

export function PackageForm({ activities, pkg }: { activities: { id: string; name: string }[]; pkg?: { id: number; title: string; days: number; price_ugx: number; activities: string; description: string | null } }) {
  const [state, action, pending] = useActionState(savePackageAction, {});
  const chosen = new Set((pkg?.activities ?? '').split(',').filter(Boolean));
  return (
    <form action={action} className="card">
      <h3>{pkg ? 'Edit package' : 'Add a package'}</h3>
      <Notice state={state} />
      {pkg && <input type="hidden" name="id" value={pkg.id} />}
      <div className="field"><label htmlFor="t">Title</label><input id="t" name="title" defaultValue={pkg?.title} placeholder="3-day Murchison Falls safari" required /></div>
      <div className="row">
        <div className="field"><label htmlFor="d">Days</label><input id="d" name="days" type="number" min={1} max={14} defaultValue={pkg?.days ?? 3} required /></div>
        <div className="field"><label htmlFor="p">Price per person (UGX)</label><input id="p" name="price_ugx" type="number" min={10000} step={1000} defaultValue={pkg?.price_ugx} required /></div>
      </div>
      <div className="field"><label>Activities included</label>
        <div className="choices">{activities.map(a => (
          <label key={a.id} className="choice" style={{ fontSize: '.82rem' }}><input type="checkbox" name="activities" value={a.id} defaultChecked={chosen.has(a.id)} style={{ display: 'inline' }} />{a.name}</label>
        ))}</div></div>
      <div className="field"><label htmlFor="desc">Description</label><textarea id="desc" name="description" rows={3} defaultValue={pkg?.description ?? ''} /></div>
      <button className="btn btn-dark" disabled={pending}>{pending ? 'Saving…' : pkg ? 'Save changes' : 'Add package'}</button>
    </form>
  );
}

export function OperatorProfileForm({ op }: { op: { company: string; licence_no: string | null; description: string | null } }) {
  const [state, action, pending] = useActionState(operatorProfileAction, {});
  return (
    <form action={action} className="card">
      <Notice state={state} />
      <div className="row">
        <div className="field"><label htmlFor="c">Company name</label><input id="c" name="company" defaultValue={op.company} required /></div>
        <div className="field"><label htmlFor="l">UTB licence number</label><input id="l" name="licence" defaultValue={op.licence_no ?? ''} /></div>
      </div>
      <div className="field"><label htmlFor="d">About your company (shown to tourists)</label><textarea id="d" name="description" rows={4} defaultValue={op.description ?? ''} /></div>
      <button className="btn btn-dark" disabled={pending}>Save profile</button>
    </form>
  );
}
