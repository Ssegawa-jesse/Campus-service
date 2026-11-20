import { activityCost, computeBudget, CostLine, feeUgx } from './budget';
import {
  ACTIVITIES, ATTRACTIONS, Activity, Fee, FUEL_UGX_PER_KM, GATE_TO_PARAA_KM, INTERESTS, Interest, MONTHS, ORIGINS, Origin,
  PARK_DRIVING_KM_PER_DAY, PARK_FEES, Residency, Slot, TIERS, TOUR_VEHICLE_PER_DAY_USD, TRANSPORT, Tier, Transport, UGX_PER_USD,
} from './data';

export type PlanInput = {
  startDate: string; // YYYY-MM-DD
  days: number; // 1-6, including travel days
  adults: number;
  children: number;
  residency: Residency;
  interests: Interest[];
  fitness: 1 | 2 | 3;
  tier: Tier;
  transport: Transport;
  origin: Origin;
  pace: 'relaxed' | 'full';
  mustDo: string[]; // activity ids the visitor picked
};

export type PlanItem = { time: string; title: string; detail: string; kind: 'travel' | 'activity' | 'stay' | 'tip'; activityId?: string; attractionId?: string; costUgx?: number };
export type DayPlan = { day: number; date: string; title: string; items: PlanItem[] };
export type Plan = {
  input: PlanInput;
  days: DayPlan[];
  costs: CostLine[];
  totalUgx: number;
  season: { m: string; season: string; note: string };
  reasons: string[];
  skipped: string[];
  warnings: string[];
  packing: string[];
};

export const DEFAULT_INPUT: PlanInput = {
  startDate: '', days: 3, adults: 2, children: 0, residency: 'fnr', interests: ['wildlife', 'waterfalls', 'boat'],
  fitness: 2, tier: 'mid', transport: 'tour', origin: 'kampala', pace: 'full', mustDo: [],
};

const SLOT_TIME: Record<Slot, string> = { dawn: '06:30', morning: '09:00', afternoon: '14:00', evening: '16:30' };
const SLOT_ORDER: Slot[] = ['dawn', 'morning', 'afternoon', 'evening'];


export function formatUgx(n: number) {
  return 'UGX ' + (Math.round(n / 1000) * 1000).toLocaleString('en-US');
}
export function formatUsd(ugx: number) {
  return 'US$' + Math.round(ugx / UGX_PER_USD).toLocaleString('en-US');
}

function addDays(iso: string, n: number) {
  const d = iso ? new Date(iso + 'T00:00:00') : new Date();
  d.setDate(d.getDate() + n);
  return d.toISOString().slice(0, 10);
}
function dayLabel(iso: string) {
  return new Date(iso + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
}
const attraction = (id: string) => ATTRACTIONS.find(a => a.id === id)!;

/** Score how well an activity suits the visitor. Returns null when it should not be suggested. */
function score(a: Activity, input: PlanInput, wet: boolean) {
  const matched = a.interests.filter(i => input.interests.includes(i));
  const must = input.mustDo.includes(a.id);
  if (!must && matched.length === 0 && a.priority < 9) return null;
  if (!must && a.requires && !a.requires.some(i => input.interests.includes(i))) return null;
  if (!must && a.fitness > input.fitness) return null;
  let s = matched.length * 10 + a.priority * 2 + (must ? 100 : 0);
  if (wet && a.wetSeasonNote) s -= 3;
  if (input.tier === 'budget' && feeUgx(a.fee, input.residency) > 200000 && !must) s -= 15;
  return { s, matched, must };
}

/** Which (day, slot) pairs an activity may use, in order of preference. */
function placements(a: Activity, n: number, origin: Origin): [number, Slot][] {
  const out: [number, Slot][] = [];
  const last = n; // day number of the last day
  const middle = Array.from({ length: Math.max(0, n - 2) }, (_, i) => i + 2);
  const firstDaySlots: Slot[] = origin === 'masindi' ? ['morning', 'afternoon', 'evening'] : ['afternoon', 'evening'];
  const lastDaySlots: Slot[] = n === 1 ? [] : ['dawn', 'morning'];
  const push = (d: number, slots: Slot[]) => a.slots.filter(s => slots.includes(s)).forEach(s => out.push([d, s]));

  switch (a.id) {
    case 'ziwa-rhinos':
      if (origin !== 'masindi') out.push([1, 'morning']);
      if (n > 1 && origin !== 'masindi') out.push([last, 'afternoon']);
      return out;
    case 'chimps':
      if (origin === 'masindi') push(1, ['dawn', 'morning']);
      if (n > 1) push(last, ['dawn', 'morning']);
      middle.forEach(d => push(d, ['dawn', 'morning']));
      return out;
    case 'top-falls':
      push(1, ['afternoon', 'evening']);
      middle.forEach(d => push(d, ['evening', 'afternoon']));
      if (n > 1) out.push([last, 'morning']);
      return out;
    default:
      middle.forEach(d => push(d, SLOT_ORDER));
      push(1, firstDaySlots);
      if (n > 1) push(last, lastDaySlots);
      return out;
  }
}

/** Slots an activity occupies when it starts in a given slot (long activities run into the next one). */
function occupied(a: Activity, start: Slot, day = 0, n = 0): Slot[] {
  if (a.id === 'ziwa-rhinos') return day === 1 ? ['morning', 'afternoon'] : []; // on the road, not inside the park
  const i = SLOT_ORDER.indexOf(start);
  if (a.hours >= 4 && (start === 'dawn' || start === 'morning')) return SLOT_ORDER.slice(i, Math.min(i + 2, 2));
  if (a.hours >= 3 && start === 'afternoon') return ['afternoon', 'evening'];
  return [start];
}

export function buildPlan(raw: PlanInput): Plan {
  const input: PlanInput = { ...raw, days: Math.min(6, Math.max(1, Math.round(raw.days))), adults: Math.max(1, raw.adults), children: Math.max(0, raw.children) };
  const n = input.days;
  const start = input.startDate || addDays('', 14);
  const monthIndex = new Date(start + 'T00:00:00').getMonth();
  const season = MONTHS[monthIndex];
  const wet = season.season === 'Wet';
  const origin = ORIGINS[input.origin];
  const persons = input.adults + input.children;
  const payingPersons = input.adults + input.children * PARK_FEES.childShare;
  const reasons: string[] = [];
  const skipped: string[] = [];
  const warnings: string[] = [];

  if (n === 1 && input.origin !== 'masindi')
    warnings.push(`A one-day visit from ${origin.label} means about ${origin.hoursToGate * 2 + 2} hours on the road. We recommend at least 2 days (1 night).`);
  if (wet) warnings.push(`${season.m} is in the rainy season. ${season.note} Pack a rain jacket and allow extra driving time.`);

  // 1. Rank activities
  const ranked = ACTIVITIES.map(a => ({ a, r: score(a, input, wet) }))
    .filter(x => {
      if (!x.r) {
        if (x.a.fitness > input.fitness && x.a.interests.some(i => input.interests.includes(i)))
          skipped.push(`${x.a.name}: left out because it is more demanding than the fitness level you chose.`);
        return false;
      }
      return true;
    })
    .sort((x, y) => y.r!.s - x.r!.s);

  // 2. Fill a timetable greedily
  const maxPerDay = input.pace === 'relaxed' ? 2 : 3;
  const used: Record<number, Set<Slot>> = {};
  const count: Record<number, number> = {};
  for (let d = 1; d <= n; d++) { used[d] = new Set(); count[d] = 0; }
  // Travel occupies part of the first and last days.
  if (input.origin !== 'masindi') { used[1].add('dawn'); }
  if (n > 1) { used[n].add('afternoon'); used[n].add('evening'); }
  if (n === 1) { used[1].add('dawn'); used[1].add('morning'); }

  const scheduled: { a: Activity; day: number; slot: Slot }[] = [];
  for (const { a, r } of ranked) {
    const limit = r!.must ? maxPerDay + 1 : maxPerDay;
    const spot = placements(a, n, input.origin).find(([d, s]) => {
      if (count[d] >= limit) return false;
      if (a.id === 'ziwa-rhinos') return d === 1 ? !used[1].has('morning') && !used[1].has('afternoon') : d === n;
      return occupied(a, s, d, n).every(o => !used[d].has(o));
    });
    if (!spot) {
      if (r!.must || r!.s >= 25) skipped.push(`${a.name}: there was no free time for it. Add a day to include it.`);
      continue;
    }
    const [d, s] = spot;
    occupied(a, s, d, n).forEach(o => used[d].add(o));
    count[d]++;
    scheduled.push({ a, day: d, slot: s });
    const matchedLabels = r!.matched.map(i => INTERESTS.find(x => x.id === i)!.label.toLowerCase());
    reasons.push(r!.must
      ? `${a.name}: you asked for it.`
      : matchedLabels.length
        ? `${a.name}: matches your interest in ${matchedLabels.slice(0, 2).join(' and ')}.`
        : `${a.name}: one of the park's highlights for a first visit.`);
    if (wet && a.wetSeasonNote) warnings.push(`${a.name}: ${a.wetSeasonNote}`);
  }

  // 3. Build the day-by-day itinerary
  const days: DayPlan[] = [];
  for (let d = 1; d <= n; d++) {
    const date = addDays(start, d - 1);
    const items: PlanItem[] = [];
    const onWayHome = d === n && n > 1 ? scheduled.filter(x => x.day === d && x.a.id === 'ziwa-rhinos') : [];
    const todays = scheduled.filter(x => x.day === d && !onWayHome.includes(x)).sort((x, y) => SLOT_ORDER.indexOf(x.slot) - SLOT_ORDER.indexOf(y.slot));

    if (d === 1 && input.origin !== 'masindi') {
      const viaZiwa = todays.some(x => x.a.id === 'ziwa-rhinos');
      items.push({ time: '06:30', kind: 'travel', title: `Depart ${origin.label}`,
        detail: `Drive north through Luweero${viaZiwa ? ', stopping at Ziwa Rhino Sanctuary,' : ''} and Masindi to Kichumbanyobo Gate (about ${origin.kmToGate} km, ${origin.hoursToGate} hours of driving). Pay park entry at the gate.` });
    }
    if (d === 1 && input.origin === 'masindi')
      items.push({ time: '07:00', kind: 'travel', title: 'Depart Masindi', detail: 'Drive about 40 km (1 hour) to Kichumbanyobo Gate and pay park entry.' });

    const addActivity = (x: { a: Activity; day: number; slot: Slot }, time?: string) => {
      const at = attraction(x.a.attractionId);
      const cost = activityCost(x.a.id, input.adults, input.children, input.residency);
      const ferry = at.zone === 'north' ? ' Cross the Nile on the Paraa ferry first.' : '';
      items.push({ time: time ?? (x.a.id === 'chimps' ? '08:00' : SLOT_TIME[x.slot]), kind: 'activity', title: x.a.name, activityId: x.a.id, attractionId: at.id,
        detail: `${x.a.description}${ferry} About ${x.a.hours} hours.${x.a.id === 'chimps' && input.children ? ' Children under 12 cannot join chimpanzee tracking.' : ''}`, costUgx: cost });
      if (at.tip) items.push({ time: '', kind: 'tip', title: 'Tip', detail: at.tip });
    };
    todays.forEach(x => addActivity(x, x.a.id === 'ziwa-rhinos' ? '10:30' : x.a.id === 'top-falls' && d === 1 ? '16:30' : undefined));

    if (d === n && n > 1) {
      const leave = todays.some(x => x.slot === 'morning' || x.a.id === 'chimps' || x.a.hours >= 4) ? '13:00' : '11:00';
      items.push({ time: leave, kind: 'travel', title: `Drive back to ${origin.label}`,
        detail: `Exit through Kichumbanyobo Gate and return via Masindi${onWayHome.length ? ', stopping at Ziwa Rhino Sanctuary' : ''} (about ${origin.kmToGate + GATE_TO_PARAA_KM} km). Expect to arrive in the evening.` });
      onWayHome.forEach(x => addActivity(x, '15:30'));
    }
    if (d < n) items.push({ time: '19:00', kind: 'stay', title: `Overnight: ${TIERS[input.tier].label} lodge`, detail: TIERS[input.tier].examples + '.' });
    if (n === 1) items.push({ time: '17:30', kind: 'travel', title: `Return to ${origin.label}`, detail: 'Leave the park before the gate closes (19:00) and drive back.' });

    const title = d === 1 ? 'Arrival and first views' : d === n ? 'Final morning and departure' : `Exploring the park (day ${d})`;
    days.push({ day: d, date, title: `${dayLabel(date)} – ${title}`, items });
  }

  // 4. Budget (shared with the budget calculator)
  const northDays = new Set(scheduled.filter(x => attraction(x.a.attractionId).zone === 'north').map(x => x.day)).size;
  const { lines: costs, total: totalUgx } = computeBudget({
    days: n, adults: input.adults, children: input.children, residency: input.residency, tier: input.tier,
    transport: input.transport, origin: input.origin, activityIds: scheduled.map(x => x.a.id), northBankDays: northDays,
  });

  // 5. Packing list
  const ids = new Set(scheduled.map(x => x.a.id));
  const packing = ['Passport or national ID and yellow fever certificate', 'Sunscreen, hat and sunglasses', 'Insect repellent and light-coloured long sleeves (tsetse flies)', 'Malaria tablets and a small first-aid kit', 'Binoculars and a camera with spare batteries', 'Cash or mobile money for fees and tips'];
  if (ids.has('chimps') || ids.has('rabongo-walk') || ids.has('hike-falls')) packing.push('Hiking boots and long trousers for forest trails');
  if (ids.has('launch') || ids.has('delta')) packing.push('A light jacket for the boat and a waterproof bag for your phone');
  if (wet) packing.push('Rain jacket and quick-drying clothes');
  if (ids.has('game-drive') || ids.has('balloon')) packing.push('A warm layer for the early-morning start');

  return { input, days, costs, totalUgx, season, reasons, skipped, warnings, packing };
}

export { TRANSPORT, feeUgx };
export type { CostLine };
