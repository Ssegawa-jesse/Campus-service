import { buildPlan, DEFAULT_INPUT, type PlanInput } from './planner';

/** Reads an itinerary shared from the planner (the "p" link parameter). Returns null if it is not valid. */
export function decodePlan(encoded: string | null | undefined): PlanInput | null {
  if (!encoded) return null;
  try {
    const json = JSON.parse(Buffer.from(encoded, 'base64').toString('utf8'));
    if (typeof json !== 'object' || !json) return null;
    return { ...DEFAULT_INPUT, ...json };
  } catch { return null; }
}

export function planSummary(encoded: string | null | undefined) {
  const input = decodePlan(encoded);
  if (!input) return null;
  const plan = buildPlan(input);
  const acts = plan.days.flatMap(d => d.items).filter(i => i.kind === 'activity').map(i => i.title);
  return { input, plan, text: `${input.days} day(s) from ${input.startDate}: ${acts.join(', ')}` };
}
