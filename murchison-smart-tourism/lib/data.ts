// All park content lives in this file so it can be updated without touching the pages.
// Fees are INDICATIVE planning figures. Confirm current tariffs with the Uganda Wildlife Authority
// (UWA) before travelling, and update the numbers here when the tariff changes.

export const PARK = {
  name: 'Murchison Falls National Park',
  shortName: 'Murchison Falls',
  manager: 'Uganda Wildlife Authority (UWA)',
  areaKm2: 3893,
  centre: { lat: 2.2, lng: 31.75 },
  // Paraa, on the Victoria Nile, used for the weather forecast.
  weatherPoint: { lat: 2.284, lng: 31.566, label: 'Paraa (Victoria Nile)' },
  contact: { email: '2400725284@mubs.ac.ug', phone: '+256 741 141 433' },
  summary:
    "Uganda's largest national park, where the Victoria Nile forces its way through a 7-metre gap and plunges 43 metres at Murchison Falls before flowing into Lake Albert. The park is known for game drives on the northern savannah, boat trips to the foot of the falls, the shoebill of the Nile delta and chimpanzees in the Budongo forest.",
};

/** Exchange rate used to show both currencies. Update when it changes. */
export const UGX_PER_USD = 3700;

export type Residency = 'fnr' | 'fr' | 'eac';
export const RESIDENCY_LABELS: Record<Residency, string> = {
  fnr: 'Foreign non-resident',
  fr: 'Foreign resident (living in East Africa)',
  eac: 'East African citizen',
};

/** A price per residency category. fnr and fr are in USD, eac is in UGX. */
export type Fee = { fnr: number; fr: number; eac: number };

export type Interest =
  | 'wildlife' | 'waterfalls' | 'boat' | 'chimps' | 'birds' | 'hiking' | 'photography' | 'fishing' | 'adventure' | 'culture';

export const INTERESTS: { id: Interest; label: string; icon: string }[] = [
  { id: 'wildlife', label: 'Big game (lions, elephants, giraffes)', icon: '🦁' },
  { id: 'waterfalls', label: 'The falls and the Nile', icon: '💧' },
  { id: 'boat', label: 'Boat cruises', icon: '🛥️' },
  { id: 'chimps', label: 'Chimpanzees and primates', icon: '🐒' },
  { id: 'birds', label: 'Birding (shoebill)', icon: '🦅' },
  { id: 'hiking', label: 'Walking and hiking', icon: '🥾' },
  { id: 'photography', label: 'Photography', icon: '📷' },
  { id: 'fishing', label: 'Sport fishing', icon: '🎣' },
  { id: 'adventure', label: 'Special experiences (balloon)', icon: '🎈' },
  { id: 'culture', label: 'Rhinos and communities on the way', icon: '🦏' },
];

export type Slot = 'dawn' | 'morning' | 'afternoon' | 'evening';
export type Zone = 'route' | 'south' | 'river' | 'north';

export type Attraction = {
  id: string;
  name: string;
  category: 'Waterfall' | 'River' | 'Wildlife' | 'Forest' | 'Gate' | 'On the way' | 'Town';
  zone: Zone;
  lat: number;
  lng: number;
  description: string;
  tip?: string;
  image?: string;
};

// Positions are approximate and meant for orientation on the map, not for navigation.
export const ATTRACTIONS: Attraction[] = [
  { id: 'top-of-falls', name: 'Top of the Falls', category: 'Waterfall', zone: 'south', lat: 2.2786, lng: 31.6857,
    description: 'Viewpoint where the whole Nile squeezes through a narrow rock gorge and thunders down. Short trails lead along the gorge to several viewpoints.',
    tip: 'Late afternoon light is best for photographs. Wear shoes with grip: the rocks are wet from the spray.', image: '/images/scenes/top-of-falls.jpg' },
  { id: 'paraa', name: 'Paraa and the ferry crossing', category: 'River', zone: 'river', lat: 2.2843, lng: 31.5655,
    description: 'The heart of the park on the Victoria Nile. Boat trips start from the Paraa jetty and the ferry takes vehicles to the northern bank for game drives.',
    tip: 'Arrive early for the first ferry if you plan a morning game drive.', image: '/images/scenes/paraa-nile.jpg' },
  { id: 'bottom-of-falls', name: 'Bottom of the Falls (by boat)', category: 'River', zone: 'river', lat: 2.2795, lng: 31.6780,
    description: 'Destination of the launch trip from Paraa, passing hippos, crocodiles, elephants and many birds on the river banks.' },
  { id: 'buligi', name: 'Buligi game tracks (north bank)', category: 'Wildlife', zone: 'north', lat: 2.2650, lng: 31.4700,
    description: 'Open savannah between the Victoria and Albert Nile with the park\'s best game viewing: lions, elephants, Rothschild\'s giraffes, buffaloes, Uganda kobs and hartebeest.',
    tip: 'Go at dawn, when predators are most active. A UWA ranger guide helps you find the lions.', image: '/images/scenes/north-bank-savannah.jpg' },
  { id: 'delta', name: 'Nile–Lake Albert delta', category: 'River', zone: 'river', lat: 2.2700, lng: 31.4200,
    description: 'Papyrus swamps where the Nile meets Lake Albert, one of the best places in Uganda to see the rare shoebill.',
    tip: 'Morning cruises give the best chance of seeing the shoebill.' },
  { id: 'kaniyo-pabidi', name: 'Kaniyo Pabidi (Budongo Forest)', category: 'Forest', zone: 'south', lat: 1.9200, lng: 31.7200,
    description: 'Tropical forest in the south of the conservation area where habituated chimpanzees can be tracked, together with forest birds and other primates.' },
  { id: 'rabongo', name: 'Rabongo Forest', category: 'Forest', zone: 'south', lat: 2.0900, lng: 31.8600,
    description: 'Small riverine forest with nature walks, black-and-white colobus monkeys and forest birds.' },
  { id: 'karuma', name: 'Karuma Falls', category: 'Waterfall', zone: 'route', lat: 2.2470, lng: 32.2440,
    description: 'Rapids on the Victoria Nile on the eastern edge of the conservation area, near the Kampala–Gulu highway.' },
  { id: 'kichumbanyobo', name: 'Kichumbanyobo Gate', category: 'Gate', zone: 'south', lat: 1.9700, lng: 31.7700,
    description: 'Main southern entrance for visitors coming from Kampala through Masindi.' },
  { id: 'tangi', name: 'Tangi Gate', category: 'Gate', zone: 'north', lat: 2.2700, lng: 32.0400,
    description: 'Eastern entrance near Karuma, used by visitors coming along the Kampala–Gulu highway.' },
  { id: 'masindi', name: 'Masindi town', category: 'Town', zone: 'route', lat: 1.6744, lng: 31.7150,
    description: 'Last main town before the southern gate, with fuel, banks, mobile money agents and supermarkets.' },
  { id: 'ziwa', name: 'Ziwa Rhino Sanctuary', category: 'On the way', zone: 'route', lat: 1.4700, lng: 32.0700,
    description: 'Sanctuary on the Kampala–Masindi route where southern white rhinos are tracked on foot. A popular stop on the way to the park.' },
];

export type Activity = {
  id: string;
  name: string;
  attractionId: string;
  interests: Interest[];
  slots: Slot[];
  hours: number;
  fitness: 1 | 2 | 3; // 1 easy, 2 moderate, 3 demanding
  fee: Fee; // per person
  groupFee?: Fee; // per group, e.g. a ranger guide for a vehicle
  requires?: Interest[]; // only suggested when the visitor picked one of these interests
  priority: number; // how essential it is for a first visit (higher = more essential)
  wetSeasonNote?: string;
  description: string;
};

export const ACTIVITIES: Activity[] = [
  { id: 'launch', name: 'Launch trip to the bottom of the falls', attractionId: 'bottom-of-falls', interests: ['boat', 'waterfalls', 'wildlife', 'birds', 'photography'],
    slots: ['afternoon', 'morning'], hours: 3, fitness: 1, fee: { fnr: 35, fr: 30, eac: 30000 }, priority: 10,
    description: 'Three-hour boat trip from Paraa up the Nile to the foot of the falls, with hippos, crocodiles, elephants and birds along the banks.' },
  { id: 'game-drive', name: 'Game drive on the Buligi tracks', attractionId: 'buligi', interests: ['wildlife', 'photography'],
    slots: ['dawn'], hours: 4, fitness: 1, fee: { fnr: 0, fr: 0, eac: 0 }, groupFee: { fnr: 20, fr: 20, eac: 20000 }, priority: 10,
    wetSeasonNote: 'Some tracks can be muddy in the rainy season; a 4x4 is recommended.',
    description: 'Early-morning drive across the northern savannah with a UWA ranger guide to find lions, elephants, giraffes and antelopes.' },
  { id: 'top-falls', name: 'Visit and walk at the Top of the Falls', attractionId: 'top-of-falls', interests: ['waterfalls', 'hiking', 'photography'],
    slots: ['afternoon', 'evening'], hours: 2, fitness: 1, fee: { fnr: 15, fr: 10, eac: 10000 }, priority: 9,
    description: 'Walk along the gorge to the viewpoints above the falls.' },
  { id: 'hike-falls', requires: ['hiking'], name: 'Hike from the bottom to the top of the falls', attractionId: 'top-of-falls', interests: ['hiking', 'waterfalls', 'photography'],
    slots: ['afternoon'], hours: 1.5, fitness: 2, fee: { fnr: 15, fr: 10, eac: 10000 }, priority: 5,
    wetSeasonNote: 'The trail is slippery after rain.',
    description: 'Get off the launch at the foot of the falls and climb the trail to the top with a ranger (arrange in advance).' },
  { id: 'delta', name: 'Delta cruise for the shoebill', attractionId: 'delta', interests: ['birds', 'boat', 'photography'],
    slots: ['dawn', 'morning'], hours: 4, fitness: 1, fee: { fnr: 40, fr: 35, eac: 40000 }, priority: 6,
    description: 'Boat cruise downstream from Paraa to the papyrus delta at Lake Albert in search of the shoebill and other water birds.' },
  { id: 'chimps', requires: ['chimps'], name: 'Chimpanzee tracking at Kaniyo Pabidi', attractionId: 'kaniyo-pabidi', interests: ['chimps', 'hiking', 'photography'],
    slots: ['dawn', 'morning'], hours: 4, fitness: 2, fee: { fnr: 90, fr: 70, eac: 80000 }, priority: 7,
    wetSeasonNote: 'Forest trails are muddy in the rainy season; wear boots.',
    description: 'Walk through Budongo forest with a guide to find a habituated chimpanzee community.' },
  { id: 'rabongo-walk', name: 'Nature walk in Rabongo Forest', attractionId: 'rabongo', interests: ['hiking', 'birds', 'chimps'],
    slots: ['morning', 'afternoon'], hours: 2.5, fitness: 1, fee: { fnr: 15, fr: 10, eac: 10000 }, priority: 3,
    description: 'Guided walk in a riverine forest with colobus monkeys and forest birds.' },
  { id: 'birding', name: 'Guided birding walk', attractionId: 'paraa', interests: ['birds', 'photography'],
    slots: ['dawn', 'morning'], hours: 3, fitness: 1, fee: { fnr: 0, fr: 0, eac: 0 }, groupFee: { fnr: 20, fr: 20, eac: 20000 }, priority: 3,
    description: 'Morning walk with a birding guide around Paraa and the river banks.' },
  { id: 'fishing', requires: ['fishing'], name: 'Sport fishing below the falls', attractionId: 'bottom-of-falls', interests: ['fishing'],
    slots: ['morning', 'afternoon'], hours: 4, fitness: 2, fee: { fnr: 50, fr: 40, eac: 50000 }, priority: 2,
    description: 'Catch-and-release fishing for Nile perch with a licensed guide (permit required).' },
  { id: 'balloon', requires: ['adventure'], name: 'Hot-air balloon safari', attractionId: 'buligi', interests: ['adventure', 'wildlife', 'photography'],
    slots: ['dawn'], hours: 3, fitness: 1, fee: { fnr: 380, fr: 380, eac: 1400000 }, priority: 2,
    description: 'Sunrise flight over the northern savannah, where available. Book well in advance.' },
  { id: 'ziwa-rhinos', requires: ['culture'], name: 'Rhino tracking at Ziwa Rhino Sanctuary', attractionId: 'ziwa', interests: ['culture', 'wildlife', 'photography'],
    slots: ['morning', 'afternoon'], hours: 2.5, fitness: 1, fee: { fnr: 50, fr: 40, eac: 40000 }, priority: 4,
    description: 'Track southern white rhinos on foot with a ranger, on the way to or from the park.' },
];

export const PARK_FEES = {
  entryPerDay: { fnr: 45, fr: 35, eac: 25000 } as Fee, // per adult per 24 hours
  childShare: 0.5, // children pay about half
  vehicleEntryPerDayUgx: 30000,
  ferryPerVehicleUgx: 25000, // per crossing at Paraa
};

export type Tier = 'budget' | 'mid' | 'luxury';
export const TIERS: Record<Tier, { label: string; perPersonNightUsd: number; mealsPerPersonDayUsd: number; examples: string }> = {
  budget: { label: 'Budget', perPersonNightUsd: 35, mealsPerPersonDayUsd: 20, examples: 'UWA bandas, campsites and budget lodges near Paraa and the southern gate' },
  mid: { label: 'Mid-range', perPersonNightUsd: 140, mealsPerPersonDayUsd: 0, examples: 'Mid-range lodges near Paraa and on the southern bank (meals usually included)' },
  luxury: { label: 'Luxury', perPersonNightUsd: 380, mealsPerPersonDayUsd: 0, examples: 'Luxury lodges along the Nile with full board' },
};

export type Transport = 'tour' | 'self' | 'arranged';
export const TRANSPORT: Record<Transport, { label: string; note: string }> = {
  tour: { label: '4x4 with driver-guide (hired)', note: 'About US$150 a day including fuel and the driver.' },
  self: { label: 'Self-drive (own vehicle)', note: 'Fuel estimated from the distance driven.' },
  arranged: { label: 'Already arranged / included in a package', note: 'No transport cost added.' },
};
export const TOUR_VEHICLE_PER_DAY_USD = 150;
export const FUEL_UGX_PER_KM = 650; // about 8 km per litre at UGX 5,200 a litre

export type Origin = 'kampala' | 'entebbe' | 'masindi';
export const ORIGINS: Record<Origin, { label: string; kmToGate: number; hoursToGate: number }> = {
  kampala: { label: 'Kampala', kmToGate: 255, hoursToGate: 4.5 },
  entebbe: { label: 'Entebbe (airport)', kmToGate: 295, hoursToGate: 5.5 },
  masindi: { label: 'Masindi', kmToGate: 40, hoursToGate: 1 },
};
export const GATE_TO_PARAA_KM = 45;
export const PARK_DRIVING_KM_PER_DAY = 60;

/** Dry and wet seasons around the park (general pattern; years differ). */
export const MONTHS = [
  { m: 'January', season: 'Dry', note: 'Hot and dry; excellent game viewing as animals gather near water.' },
  { m: 'February', season: 'Dry', note: 'Hot and dry; one of the best months for game drives.' },
  { m: 'March', season: 'Wet', note: 'Rains begin; landscapes turn green.' },
  { m: 'April', season: 'Wet', note: 'Wettest period; some tracks can be muddy.' },
  { m: 'May', season: 'Wet', note: 'Rainy; fewer visitors and lush scenery.' },
  { m: 'June', season: 'Dry', note: 'Dry season begins; very good for all activities.' },
  { m: 'July', season: 'Dry', note: 'Dry and popular; book lodges early.' },
  { m: 'August', season: 'Dry', note: 'Mostly dry; good wildlife viewing.' },
  { m: 'September', season: 'Wet', note: 'Short rains may start; still good boat trips.' },
  { m: 'October', season: 'Wet', note: 'Short rains; afternoon showers common.' },
  { m: 'November', season: 'Wet', note: 'Short rains continue; excellent birding.' },
  { m: 'December', season: 'Dry', note: 'Dry season returns; busy holiday period.' },
];

export const TRAVEL_NOTICES = [
  { title: 'Confirm fees before you travel', body: 'Park and activity fees in this system are planning estimates. Confirm the current UWA tariff before paying.' },
  { title: 'Yellow fever certificate', body: 'Visitors to Uganda need a valid yellow fever vaccination certificate.' },
  { title: 'Paraa ferry', body: 'The ferry runs at set times during the day. Check the schedule at the gate and plan game drives around it.' },
  { title: 'Tsetse flies', body: 'Tsetse flies are common in wooded areas. Wear light-coloured long sleeves and use insect repellent.' },
  { title: 'Money', body: 'Card payments are not accepted everywhere. Carry some cash or mobile money; Masindi is the last main town with banks.' },
  { title: 'Malaria', body: 'Murchison Falls is in a malaria area. Take preventive medicine and sleep under a mosquito net.' },
];

/** 360° scenes for the built-in viewer. Replace the images with your own 360° photos (2:1 JPG). */
export type Scene = {
  id: string;
  title: string;
  image: string;
  hotspots: { pitch: number; yaw: number; type: 'scene' | 'info'; text: string; sceneId?: string }[];
};
export const SCENES: Scene[] = [
  { id: 'top-of-falls', title: 'Top of the Falls', image: '/images/scenes/top-of-falls.jpg', hotspots: [
    { pitch: -6, yaw: 20, type: 'info', text: 'The Nile squeezes through a 7-metre gap here before dropping 43 metres.' },
    { pitch: -4, yaw: 150, type: 'scene', text: 'Go to Paraa and the Nile', sceneId: 'paraa-nile' },
  ] },
  { id: 'paraa-nile', title: 'Paraa and the Victoria Nile', image: '/images/scenes/paraa-nile.jpg', hotspots: [
    { pitch: -8, yaw: -40, type: 'info', text: 'Launch trips to the bottom of the falls and delta cruises leave from the Paraa jetty.' },
    { pitch: -4, yaw: 90, type: 'scene', text: 'Cross to the northern savannah', sceneId: 'north-bank-savannah' },
    { pitch: -4, yaw: -150, type: 'scene', text: 'Back to the Top of the Falls', sceneId: 'top-of-falls' },
  ] },
  { id: 'north-bank-savannah', title: 'Northern savannah (Buligi)', image: '/images/scenes/north-bank-savannah.jpg', hotspots: [
    { pitch: -6, yaw: 60, type: 'info', text: 'Lions, elephants, Rothschild\'s giraffes and Uganda kobs are seen on the Buligi game tracks.' },
    { pitch: -4, yaw: -120, type: 'scene', text: 'Back to Paraa', sceneId: 'paraa-nile' },
  ] },
];

/**
 * H5P virtual tours. Build a "Virtual Tour (360)" in H5P (for example with the free Lumi app),
 * then either export it as one HTML file into public/h5p/ and use src: '/h5p/<file>.html',
 * or paste the embed URL from h5p.com / your LMS.
 */
export const H5P_TOURS: { title: string; src: string; description: string }[] = [
  // { title: 'Murchison Falls 360° tour', src: '/h5p/murchison-tour.html', description: 'Built with H5P from our own 360° photos.' },
];

export const GALLERY = [
  { src: '/images/hero.jpg', caption: 'The Victoria Nile' },
  { src: '/images/gallery/gallery-1.jpg', caption: 'Savannah landscape' },
  { src: '/images/gallery/gallery-2.jpg', caption: 'Riverine scenery' },
];
