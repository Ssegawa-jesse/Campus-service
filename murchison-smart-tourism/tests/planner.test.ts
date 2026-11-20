import { test } from 'node:test';
import assert from 'node:assert/strict';
import { buildPlan, DEFAULT_INPUT, PlanInput } from '../lib/planner';
import { activityCost, computeBudget } from '../lib/budget';
import { guideAnswer, parkKnowledge } from '../lib/assistant';
import { PARK_FEES, UGX_PER_USD } from '../lib/data';

const base: PlanInput = { ...DEFAULT_INPUT, startDate: '2026-12-10' };
const activityIds = (p: ReturnType<typeof buildPlan>) => p.days.flatMap(d => d.items).filter(i => i.kind === 'activity').map(i => i.activityId);

test('a 3-day plan has one entry per day, starts and ends with travel', () => {
  const p = buildPlan(base);
  assert.equal(p.days.length, 3);
  assert.equal(p.days[0].items[0].kind, 'travel');
  assert.ok(p.days[2].items.some(i => i.kind === 'travel' && i.title.startsWith('Drive back')));
  assert.equal(p.days.filter(d => d.items.some(i => i.kind === 'stay')).length, 2, 'two overnight stays');
});

test('wildlife and boat lovers get a dawn game drive and a launch trip', () => {
  const p = buildPlan(base);
  const items = p.days.flatMap(d => d.items);
  const drive = items.find(i => i.activityId === 'game-drive');
  assert.ok(drive, 'game drive included');
  assert.equal(drive!.time, '06:30');
  assert.ok(items.some(i => i.activityId === 'launch'));
});

test('activities above the chosen fitness level are left out and explained', () => {
  const p = buildPlan({ ...base, fitness: 1, interests: ['chimps', 'hiking'] });
  assert.ok(!activityIds(p).includes('chimps'));
  assert.ok(p.skipped.some(s => s.startsWith('Chimpanzee tracking')));
});

test('special-interest extras are only added when the interest is chosen', () => {
  const without = buildPlan(base);
  assert.ok(!activityIds(without).includes('balloon'));
  const withBalloon = buildPlan({ ...base, interests: [...base.interests, 'adventure'] });
  assert.ok(activityIds(withBalloon).includes('balloon'));
});

test('a must-do activity is always scheduled when there is time', () => {
  const p = buildPlan({ ...base, interests: ['waterfalls'], mustDo: ['delta'] });
  assert.ok(activityIds(p).includes('delta'));
  assert.ok(p.reasons.some(r => r.includes('you asked for it')));
});

test('relaxed pace never schedules more than two activities a day', () => {
  const p = buildPlan({ ...base, days: 4, pace: 'relaxed', interests: ['wildlife', 'waterfalls', 'boat', 'birds', 'chimps', 'hiking'], fitness: 3 });
  for (const d of p.days) assert.ok(d.items.filter(i => i.kind === 'activity').length <= 2, d.title);
});

test('no two activities on the same day share a start time', () => {
  for (const days of [2, 3, 4, 5, 6]) {
    const p = buildPlan({ ...base, days, interests: ['wildlife', 'waterfalls', 'boat', 'birds', 'chimps', 'hiking', 'culture'], fitness: 3 });
    for (const d of p.days) {
      const times = d.items.filter(i => i.kind === 'activity').map(i => i.time);
      assert.equal(new Set(times).size, times.length, `${days}-day plan, ${d.title}`);
    }
  }
});

test('rainy-season trips carry a warning', () => {
  const p = buildPlan({ ...base, startDate: '2026-04-15' });
  assert.equal(p.season.season, 'Wet');
  assert.ok(p.warnings.some(w => w.includes('rainy season')));
});

test('a day trip from Kampala warns about the driving time', () => {
  const p = buildPlan({ ...base, days: 1 });
  assert.ok(p.warnings.some(w => w.includes('one-day visit')));
});

test('park entry is charged per adult per day, children at half price', () => {
  const b = computeBudget({ days: 2, adults: 2, children: 2, residency: 'fnr', tier: 'budget', transport: 'arranged', origin: 'kampala', activityIds: [] });
  const entry = b.lines.find(l => l.label === 'Park entry')!.ugx;
  assert.equal(entry, PARK_FEES.entryPerDay.fnr * UGX_PER_USD * 3 * 2);
});

test('East African citizens are charged in shillings', () => {
  assert.equal(activityCost('launch', 1, 0, 'eac'), 30000);
});

test('children are not charged for chimpanzee tracking, which they cannot join', () => {
  assert.equal(activityCost('chimps', 2, 3, 'eac'), activityCost('chimps', 2, 0, 'eac'));
});

test('the planner total matches the budget calculator for the same choices', () => {
  const p = buildPlan(base);
  assert.equal(Math.round(p.totalUgx), Math.round(p.costs.slice(0, -1).reduce((s, c) => s + c.ugx, 0) * 1.1));
});

test('the built-in assistant answers topic questions from the park data', () => {
  assert.match(guideAnswer('How much is the boat trip?'), /Launch trip/);
  assert.match(guideAnswer('Can children track chimpanzees?'), /under 12/);
  assert.match(guideAnswer('Where can I see the shoebill?'), /shoebill/i);
  assert.match(guideAnswer('When is the best time to visit?'), /dry seasons/);
  assert.match(guideAnswer('hello'), /I can help/);
});

test('the AI assistant is given the park fees and activities', () => {
  const k = parkKnowledge();
  assert.match(k, /PARK ENTRY/);
  assert.match(k, /Chimpanzee tracking/);
});
