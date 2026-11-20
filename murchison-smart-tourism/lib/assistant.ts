import { ACTIVITIES, ATTRACTIONS, MONTHS, ORIGINS, PARK, PARK_FEES, TIERS, TRAVEL_NOTICES, UGX_PER_USD } from './data';

/** Park facts given to the AI model so that its answers stay grounded in this system's data. */
export function parkKnowledge(): string {
  const acts = ACTIVITIES.map(a =>
    `- ${a.name}: ${a.description} Duration about ${a.hours} h. Fee per person: US$${a.fee.fnr} (foreign non-resident), US$${a.fee.fr} (foreign resident), UGX ${a.fee.eac.toLocaleString()} (East African citizen)` +
    (a.groupFee ? `; plus a guide fee of about US$${a.groupFee.fnr} per group` : '') + (a.wetSeasonNote ? ` Rainy season: ${a.wetSeasonNote}` : ''));
  const places = ATTRACTIONS.map(a => `- ${a.name} (${a.category}): ${a.description}${a.tip ? ' Tip: ' + a.tip : ''}`);
  return [
    `PARK: ${PARK.name}. ${PARK.summary} Managed by the ${PARK.manager}.`,
    `PARK ENTRY per adult per 24 h: US$${PARK_FEES.entryPerDay.fnr} (foreign non-resident), US$${PARK_FEES.entryPerDay.fr} (foreign resident), UGX ${PARK_FEES.entryPerDay.eac.toLocaleString()} (East African citizen). Children pay about half. Vehicle entry about UGX ${PARK_FEES.vehicleEntryPerDayUgx.toLocaleString()} a day; Paraa ferry about UGX ${PARK_FEES.ferryPerVehicleUgx.toLocaleString()} per vehicle per crossing.`,
    'ACTIVITIES:', ...acts,
    'PLACES:', ...places,
    'GETTING THERE: ' + Object.values(ORIGINS).map(o => `from ${o.label} about ${o.kmToGate} km (${o.hoursToGate} h) to Kichumbanyobo Gate`).join('; ') + '; then about 45 km to Paraa.',
    'ACCOMMODATION (per person per night): ' + Object.values(TIERS).map(t => `${t.label} about US$${t.perPersonNightUsd} — ${t.examples}`).join('; ') + '.',
    'SEASONS: ' + MONTHS.map(m => `${m.m}: ${m.season} (${m.note})`).join(' '),
    'TRAVEL NOTICES: ' + TRAVEL_NOTICES.map(n => `${n.title}: ${n.body}`).join(' '),
    `Exchange rate used: US$1 = UGX ${UGX_PER_USD.toLocaleString()}. All prices are planning estimates to be confirmed with UWA.`,
    `Contact for this platform: ${PARK.contact.email}, ${PARK.contact.phone}.`,
  ].join('\n');
}

const has = (q: string, words: string[]) => words.some(w => q.includes(w));
const usd = (n: number) => `US$${n}`;

/** Answers common questions from the park data when no AI key is configured (or the AI is unavailable). */
export function guideAnswer(question: string): string {
  const q = question.toLowerCase();
  const act = (id: string) => ACTIVITIES.find(a => a.id === id)!;
  const line = (id: string) => { const a = act(id); return `• ${a.name} — about ${a.hours} h, ${usd(a.fee.fnr)} per person (foreign non-resident) / UGX ${a.fee.eac.toLocaleString()} (East African citizen). ${a.description}`; };

  if (has(q, ['chimp', 'primate', 'budongo', 'kaniyo', 'monkey']))
    return `${line('chimps')}\n\nIt takes place in the morning in Budongo forest (Kaniyo Pabidi), in the south of the park, so it fits best on your arrival or departure day. Children under 12 cannot track chimpanzees. Wear long trousers and boots.`;
  if (has(q, ['shoebill', 'bird', 'delta', 'lake albert']))
    return `${line('delta')}\n\nMorning cruises give the best chance of seeing the shoebill. A guided birding walk around Paraa is also available.`;
  if (has(q, ['boat', 'launch', 'cruise', 'bottom of the falls', 'hippo', 'crocodile']))
    return `${line('launch')}\n\nTrips leave from the Paraa jetty in the morning and afternoon. If you enjoy walking, you can get off at the foot of the falls and hike to the top with a ranger (arrange in advance).`;
  if (has(q, ['game drive', 'lion', 'elephant', 'giraffe', 'animal', 'safari', 'wildlife', 'buffalo', 'buligi']))
    return `${line('game-drive')}\n\nThe best game viewing is on the northern bank (Buligi tracks), reached by the Paraa ferry. Start at dawn, when lions are most active, and take a UWA ranger guide.`;
  if (has(q, ['fish']))
    return line('fishing') + '\n\nA fishing permit is required and fishing is catch-and-release.';
  if (has(q, ['balloon']))
    return line('balloon') + '\n\nAvailability is limited, so book well in advance.';
  if (has(q, ['rhino', 'ziwa']))
    return line('ziwa-rhinos') + '\n\nZiwa is on the Kampala–Masindi road, so it is easy to visit on the way to or from the park.';
  if (has(q, ['top of the falls', 'waterfall', 'the falls', 'gorge', 'hike']))
    return `At the Top of the Falls the whole Nile squeezes through a narrow gorge before dropping. Trails lead to several viewpoints (about 2 hours). It is on the way in from the southern gate, so it fits well on your first afternoon. Late afternoon light is best for photos.`;
  if (has(q, ['fee', 'price', 'cost', 'how much', 'entry', 'charge', 'pay', 'money', 'budget', 'expens']))
    return `Park entry per adult per day is about ${usd(PARK_FEES.entryPerDay.fnr)} for foreign non-residents, ${usd(PARK_FEES.entryPerDay.fr)} for foreign residents and UGX ${PARK_FEES.entryPerDay.eac.toLocaleString()} for East African citizens (children about half).\n\nPopular activities:\n${line('launch')}\n${line('game-drive')}\n${line('chimps')}\n\nFor a full estimate including accommodation and transport, use the Travel budget page. These are planning figures — confirm current UWA fees before paying.`;
  if (has(q, ['when', 'best time', 'season', 'month', 'rain', 'dry', 'weather', 'climate']))
    return `The main dry seasons are December–February and June–August, the best times for game drives. March–May and September–November are rainier: the park is green, there are fewer visitors and birding is excellent, but some tracks can be muddy.\n\nSee the Travel updates page for the live 16-day forecast at Paraa.`;
  if (has(q, ['get there', 'route', 'direction', 'drive', 'distance', 'far', 'kampala', 'entebbe', 'masindi', 'road', 'transport', 'how do i get']))
    return `From Kampala it is about ${ORIGINS.kampala.kmToGate} km (${ORIGINS.kampala.hoursToGate} hours) via Luweero and Masindi to Kichumbanyobo Gate, then about 45 km to Paraa. From Entebbe add about an hour. Many visitors stop at Ziwa Rhino Sanctuary on the way. The Map & routes page shows every attraction and gives directions.`;
  if (has(q, ['stay', 'lodge', 'hotel', 'accommodation', 'sleep', 'camp', 'banda']))
    return Object.values(TIERS).map(t => `• ${t.label}: about US$${t.perPersonNightUsd} per person per night — ${t.examples}.`).join('\n') + '\n\nBook early for July–August and December.';
  if (has(q, ['pack', 'bring', 'wear', 'clothes', 'carry']))
    return 'Bring: passport and yellow fever certificate, sunscreen, hat, insect repellent, light-coloured long sleeves (tsetse flies), binoculars, a camera, a light jacket for boat trips and early drives, hiking boots for forest walks, a rain jacket in the rainy months, and some cash or mobile money.';
  if (has(q, ['safe', 'malaria', 'tsetse', 'yellow fever', 'vaccin', 'health', 'danger']))
    return TRAVEL_NOTICES.map(n => `• ${n.title}: ${n.body}`).join('\n') + '\n\nAlways follow ranger instructions and stay in your vehicle on game drives unless told otherwise.';
  if (has(q, ['ferry']))
    return 'The Paraa ferry carries vehicles across the Nile to the northern bank for game drives. It runs at set times during the day, so check the schedule at the gate and catch an early crossing for a dawn game drive.';
  if (has(q, ['how many days', 'how long', 'itinerary', 'plan', 'schedule', 'days']))
    return 'We recommend at least 3 days (2 nights) from Kampala: day 1 drive in and visit the Top of the Falls; day 2 a dawn game drive and an afternoon launch trip; day 3 a morning activity (delta cruise or chimpanzee tracking) and drive back. The Smart itinerary page builds a timed plan for your own dates and interests.';
  if (has(q, ['contact', 'phone', 'email', 'call']))
    return `You can contact the platform team at ${PARK.contact.email} or ${PARK.contact.phone}. For official bookings and permits, contact the Uganda Wildlife Authority.`;
  if (has(q, ['recommend', 'what to do', 'activities', 'things to do', 'suggest', 'see']))
    return `The highlights of ${PARK.shortName} are:\n${['launch', 'game-drive', 'top-falls', 'delta', 'chimps'].map(line).join('\n')}\n\nTell the Smart itinerary page what you like and it will choose and time them for you.`;
  return `I can help with park fees, the best time to visit, getting there, activities (game drives, boat trips, chimpanzees, the shoebill), accommodation, what to pack and safety. Try asking, for example, "How much is the boat trip?" or "How many days do I need?"`;
}
