import {
  ACTIVITIES, ATTRACTIONS, Fee, FUEL_UGX_PER_KM, GATE_TO_PARAA_KM, ORIGINS, Origin, PARK_DRIVING_KM_PER_DAY, PARK_FEES, Residency,
  TIERS, TOUR_VEHICLE_PER_DAY_USD, Tier, Transport, UGX_PER_USD,
} from './data';

export type CostLine = { label: string; detail: string; ugx: number };
export type BudgetInput = {
  days: number; adults: number; children: number; residency: Residency; tier: Tier; transport: Transport; origin: Origin;
  activityIds: string[];
  northBankDays?: number; // days that need the Paraa ferry (worked out from the activities when not given)
};

/** Convert a fee for the visitor's residency category into Uganda shillings. */
export function feeUgx(fee: Fee, r: Residency): number {
  return r === 'eac' ? fee.eac : fee[r] * UGX_PER_USD;
}

/** Cost of one activity for the group. Children under 12 cannot track chimpanzees. */
export function activityCost(id: string, adults: number, children: number, r: Residency) {
  const a = ACTIVITIES.find(x => x.id === id);
  if (!a) return 0;
  const kids = a.id === 'chimps' ? 0 : children;
  return feeUgx(a.fee, r) * (adults + kids) + (a.groupFee ? feeUgx(a.groupFee, r) : 0);
}

function needsFerry(activityId: string) {
  const a = ACTIVITIES.find(x => x.id === activityId);
  return ATTRACTIONS.find(x => x.id === a?.attractionId)?.zone === 'north';
}

export function computeBudget(b: BudgetInput): { lines: CostLine[]; total: number } {
  const n = Math.max(1, b.days);
  const nights = Math.max(0, n - 1);
  const persons = b.adults + b.children;
  const paying = b.adults + b.children * PARK_FEES.childShare;
  const lines: CostLine[] = [];

  lines.push({ label: 'Park entry', detail: `${n} day(s) × ${b.adults} adult(s)${b.children ? ` + ${b.children} child(ren) at half price` : ''}`,
    ugx: feeUgx(PARK_FEES.entryPerDay, b.residency) * paying * n });
  const act = b.activityIds.reduce((s, id) => s + activityCost(id, b.adults, b.children, b.residency), 0);
  lines.push({ label: 'Activities', detail: `${b.activityIds.length} activit${b.activityIds.length === 1 ? 'y' : 'ies'}`, ugx: act });
  lines.push({ label: 'Accommodation', detail: `${nights} night(s), ${TIERS[b.tier].label.toLowerCase()} (children at half rate)`,
    ugx: TIERS[b.tier].perPersonNightUsd * UGX_PER_USD * paying * nights });
  if (TIERS[b.tier].mealsPerPersonDayUsd)
    lines.push({ label: 'Meals', detail: `${n} day(s) for ${persons} person(s)`, ugx: TIERS[b.tier].mealsPerPersonDayUsd * UGX_PER_USD * persons * n });

  if (b.transport !== 'arranged') {
    const origin = ORIGINS[b.origin];
    const km = (origin.kmToGate + GATE_TO_PARAA_KM) * 2 + PARK_DRIVING_KM_PER_DAY * n;
    if (b.transport === 'tour')
      lines.push({ label: 'Vehicle and driver-guide', detail: `${n} day(s) × US$${TOUR_VEHICLE_PER_DAY_USD}`, ugx: TOUR_VEHICLE_PER_DAY_USD * UGX_PER_USD * n });
    else lines.push({ label: 'Fuel', detail: `About ${km} km of driving`, ugx: km * FUEL_UGX_PER_KM });
    const north = b.northBankDays ?? (b.activityIds.some(needsFerry) ? 1 : 0);
    lines.push({ label: 'Vehicle entry and ferry', detail: `Vehicle entry for ${n} day(s)${north ? ` and ${north * 2} ferry crossing(s)` : ''}`,
      ugx: PARK_FEES.vehicleEntryPerDayUgx * n + PARK_FEES.ferryPerVehicleUgx * north * 2 });
  }

  const subtotal = lines.reduce((s, c) => s + c.ugx, 0);
  lines.push({ label: 'Contingency (10%)', detail: 'Tips, drinks, souvenirs and price changes', ugx: subtotal * 0.1 });
  return { lines, total: subtotal * 1.1 };
}
