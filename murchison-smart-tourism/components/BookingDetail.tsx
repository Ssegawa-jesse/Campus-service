import { all, one, type Booking, type Package, type Payment } from '@/lib/db';
import { planSummary } from '@/lib/plancode';
import { formatShs, METHODS } from '@/lib/payments';
import { formatUgx } from '@/lib/planner';
import { StatusBadge } from './DashShell';

const FLOW = ['enquiry', 'quoted', 'paid', 'confirmed', 'completed'];

export async function loadBooking(where: string, args: (string | number)[]) {
  const b = await one<Booking & { company: string; tourist_name: string; tourist_email: string; tourist_phone: string | null }>(
    `SELECT b.*, o.company, t.name AS tourist_name, t.email AS tourist_email, t.phone AS tourist_phone
     FROM bookings b JOIN operators o ON o.id = b.operator_id JOIN users t ON t.id = b.tourist_id WHERE ${where}`, args);
  if (!b) return null;
  const payments = await all<Payment>('SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC', [b.id]);
  const pkg = b.package_id ? await one<Package>('SELECT * FROM packages WHERE id = ?', [b.package_id]) : null;
  return { b, payments, pkg, plan: planSummary(b.plan) };
}

export default function BookingDetail({ data, showTourist }: { data: NonNullable<Awaited<ReturnType<typeof loadBooking>>>; showTourist?: boolean }) {
  const { b, payments, pkg, plan } = data;
  const step = FLOW.indexOf(b.status);
  return (
    <>
      <div className="steps-line">{FLOW.map((s, i) => <span key={s} className={step >= i ? 'done' : ''}>{s}</span>)}</div>
      {['cancelled', 'declined'].includes(b.status) && <div className="notice warn">This booking was {b.status}.</div>}
      <div className="grid grid-2">
        <div className="card">
          <h3>Trip details <StatusBadge s={b.status} /></h3>
          <table className="data"><tbody>
            <tr><th>Reference</th><td>{b.reference}</td></tr>
            <tr><th>Tour operator</th><td>{b.company}</td></tr>
            {showTourist && <tr><th>Tourist</th><td>{b.tourist_name}<div className="small muted">{b.tourist_email} · {b.tourist_phone}</div></td></tr>}
            <tr><th>Arrival</th><td>{b.start_date} · {b.days} day(s)</td></tr>
            <tr><th>People</th><td>{b.adults} adult(s){b.children ? `, ${b.children} child(ren)` : ''}</td></tr>
            {pkg && <tr><th>Package</th><td>{pkg.title} (UGX {pkg.price_ugx.toLocaleString()} per person)</td></tr>}
            {b.message && <tr><th>Message</th><td>{b.message}</td></tr>}
            <tr><th>Quote</th><td>{b.quote_ugx ? <strong>{formatShs(b.quote_ugx)}</strong> : 'Waiting for the operator'}{b.quote_note && <div className="small muted">{b.quote_note}</div>}</td></tr>
          </tbody></table>
        </div>
        <div className="card">
          <h3>Payments</h3>
          {payments.length === 0 ? <p className="muted small">No payments yet.</p> : (
            <table className="data"><thead><tr><th>Date</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead><tbody>
              {payments.map(p => <tr key={p.id}><td className="small">{p.created_at}</td><td>{METHODS[p.method]}{p.phone ? <div className="small muted">{p.phone}</div> : null}</td><td>{formatShs(p.amount_ugx)}</td><td><StatusBadge s={p.status} /></td></tr>)}
            </tbody></table>)}
        </div>
      </div>
      {plan && (
        <div className="card" style={{ marginTop: 16 }}>
          <h3>Smart itinerary sent with this booking</h3>
          <p className="small muted">System estimate for this plan: {formatUgx(plan.plan.totalUgx)}</p>
          {plan.plan.days.map(d => (
            <div key={d.day} style={{ marginBottom: 8 }}><strong>Day {d.day}: {d.title}</strong>
              <ul className="clean small">{d.items.filter(i => i.kind !== 'tip').map((i, k) => <li key={k}>{i.time && `${i.time} – `}{i.title}</li>)}</ul></div>
          ))}
        </div>
      )}
    </>
  );
}
