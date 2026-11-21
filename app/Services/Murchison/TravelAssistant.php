<?php

namespace App\Services\Murchison;

use App\Models\Activity;
use App\Models\AssistantLog;
use App\Models\Park;
use App\Models\Place;
use App\Models\TravelUpdate;
use Illuminate\Support\Facades\Log;

/**
 * AI TRAVEL ASSISTANT.
 *
 * Two layers:
 *  1. Claude (Anthropic API) — used when ANTHROPIC_API_KEY is set and the anthropic-ai/sdk package is installed.
 *     The model is grounded with a "park knowledge" system prompt built live from the database
 *     (activities + UWA prices, places, live travel updates, seasons), so answers stay on Murchison Falls.
 *  2. A built-in knowledge base (intent matching) — always available, offline, free. It is also the
 *     fallback when the API is unavailable or declines a request.
 * Itinerary questions ("3 days, 1,500 dollars…") are answered by running the real Smart Itinerary engine.
 */
class TravelAssistant
{
    public function __construct(private ItineraryEngine $engine, private BudgetCalculator $budget)
    {
    }

    /** @param array<int,array{role:string,content:string}> $history */
    public function ask(string $question, array $history = [], ?int $userId = null): array
    {
        $question = trim(mb_substr($question, 0, 1000));
        [$answer, $intent, $plan] = $this->knowledgeBase($question);
        $source = 'knowledge-base';

        if ($this->aiEnabled() && $intent !== 'itinerary') {
            $ai = $this->askClaude($question, $history);
            if ($ai !== null) {
                [$answer, $source] = [$ai, 'claude'];
            }
        }

        try {
            AssistantLog::create(['user_id' => $userId, 'question' => $question, 'answer' => $answer, 'source' => $source, 'intent' => $intent]);
        } catch (\Throwable) {
            // logging must never break the chat
        }

        return ['answer' => $answer, 'source' => $source, 'intent' => $intent, 'plan' => $plan];
    }

    public function aiEnabled(): bool
    {
        return (bool) config('services.anthropic.key') && class_exists(\Anthropic\Client::class);
    }

    private function askClaude(string $question, array $history): ?string
    {
        try {
            $client = new \Anthropic\Client(apiKey: config('services.anthropic.key'));
            $messages = collect($history)->take(-8)
                ->filter(fn ($m) => in_array($m['role'] ?? '', ['user', 'assistant'], true) && trim($m['content'] ?? '') !== '')
                ->map(fn ($m) => ['role' => $m['role'], 'content' => mb_substr($m['content'], 0, 2000)])->values()->all();
            $messages[] = ['role' => 'user', 'content' => $question];

            $message = $client->messages->create(
                model: config('services.anthropic.model', 'claude-opus-5-5'),
                maxTokens: 2000,
                system: [['type' => 'text', 'text' => $this->systemPrompt(), 'cacheControl' => ['type' => 'ephemeral']]],
                outputConfig: ['effort' => 'low'],
                messages: $messages,
            );

            if ($message->stopReason === 'refusal') {
                return null; // fall back to the built-in knowledge base
            }
            $text = '';
            foreach ($message->content as $block) {
                if ($block->type === 'text') {
                    $text .= $block->text;
                }
            }

            return trim($text) ?: null;
        } catch (\Throwable $e) {
            Log::warning('Claude assistant unavailable: '.$e->getMessage());

            return null;
        }
    }

    /** Grounding facts for the model, built from the live database. */
    public function systemPrompt(): string
    {
        $cfg = config('murchison');
        $park = Park::where('slug', $cfg['park_slug'])->first();
        $acts = Activity::with('place')->active()->get()->map(fn (Activity $a) => "- {$a->name} ({$a->place?->name}): {$a->description} About {$a->hours} h; slots: ".implode('/', $a->slots ?? [])."; UWA fee per person: US\${$a->price_fnr_usd} Foreign Non-Resident, US\${$a->price_fr_usd} Foreign Resident, UGX ".number_format($a->price_eac_ugx).' East African Citizen'.($a->min_age ? "; minimum age {$a->min_age}" : '').($a->wet_season_note ? "; wet season: {$a->wet_season_note}" : ''))->implode("\n");
        $places = Place::where('is_active', true)->get()->map(fn (Place $p) => "- {$p->name} ({$p->typeLabel()}, {$p->latitude},{$p->longitude}): {$p->description}")->implode("\n");
        $updates = TravelUpdate::current()->get()->map(fn ($u) => "- [{$u->severity}] {$u->categoryLabel()}: {$u->title} — {$u->body}")->implode("\n") ?: '- none';
        $tiers = collect($cfg['tiers'])->map(fn ($t) => "{$t['label']} ≈US\${$t['usd_per_person_night']} pp/night ({$t['examples']})")->implode('; ');
        $months = collect($cfg['months'])->map(fn ($m, $i) => date('F', mktime(0, 0, 0, $i, 1)).": {$m[0]} – {$m[1]}")->implode(' ');

        return <<<TXT
        You are the AI Travel Assistant of the Murchison Falls Smart Trip Planning platform (a Makerere University Business School research project). You ONLY help with Murchison Falls National Park, Uganda. If asked about another park or an unrelated topic, politely say you cover Murchison Falls only.
        Answer in a friendly, concise way (under 180 words), using the facts below. Prices are indicative UWA planning figures; remind visitors to confirm with the Uganda Wildlife Authority. Point visitors to the platform's pages when useful: Smart Itinerary, Budget Calculator, Smart Navigation map, Travel Updates, 360° Virtual Exploration.
        Never invent fees, schedules or gate times that are not below.

        PARK: {$park?->name}. {$park?->description}
        PARK ENTRY per adult per 24 h: US\${$cfg['entry']['fnr']} (Foreign Non-Resident), US\${$cfg['entry']['fr']} (Foreign Resident), UGX {$cfg['entry']['eac']} (East African Citizen); children about half. Vehicle entry ≈UGX {$cfg['vehicle_entry_per_day_ugx']}/day; Paraa ferry ≈UGX {$cfg['ferry_per_crossing_ugx']} per vehicle per crossing.
        ACTIVITIES:
        {$acts}
        PLACES (gates, jetty, trail, airfield, attractions):
        {$places}
        LIVE TRAVEL UPDATES:
        {$updates}
        ACCOMMODATION: {$tiers}.
        SEASONS: {$months}
        GETTING THERE: Kampala → Masindi → Kichumbanyobo Gate ≈255 km (4.5 h), then 45 km to Paraa. Pakuba Airfield serves scheduled/charter flights from Entebbe/Kajjansi.
        TXT;
    }

    /** Offline intent-matching answers. Returns [answer, intent, plan|null]. */
    public function knowledgeBase(string $question): array
    {
        $q = mb_strtolower($question);
        $has = fn (array $w) => collect($w)->contains(fn ($x) => str_contains($q, $x));
        $act = fn (string $slug) => Activity::where('slug', $slug)->first();
        $line = function (?Activity $a) {
            if (! $a) {
                return '';
            }

            return "• {$a->name} — about {$a->hours} h; US\$".rtrim(rtrim(number_format($a->price_fnr_usd, 2), '0'), '.').' (Foreign Non-Resident) / US$'.rtrim(rtrim(number_format($a->price_fr_usd, 2), '0'), '.').' (Foreign Resident) / UGX '.number_format($a->price_eac_ugx).' (East African Citizen). '.$a->description;
        };
        $cfg = config('murchison');

        // Itinerary recommendation from budget + days ("plan 3 days with $1500")
        if ($has(['itinerary', 'plan my', 'plan a', 'recommend a trip', 'days with', 'day trip', 'schedule for']) || preg_match('/\b(\d)\s*(day|days|night|nights)\b/', $q)) {
            preg_match('/\b(\d)\s*(?:day|days)\b/', $q, $d);
            preg_match('/(?:\$|usd|us\$)\s*([\d,]+)|([\d,]+)\s*(?:usd|dollars)/', $q, $usd);
            preg_match('/(?:ugx|shs|shillings)\s*([\d,]+)|([\d,]+)\s*(?:ugx|shs|shillings)/', $q, $ugx);
            $days = (int) ($d[1] ?? 3);
            $limit = null;
            if ($usd) {
                $limit = usd_to_ugx((int) str_replace(',', '', $usd[1] ?: $usd[2]));
            } elseif ($ugx) {
                $limit = (int) str_replace(',', '', $ugx[1] ?: $ugx[2]);
            }
            $cat = $has(['east african', 'ugandan', 'kenyan', 'citizen', 'ugx', 'shillings']) ? 'eac' : ($has(['resident', 'expat']) ? 'fr' : 'fnr');
            $tier = $limit && $limit / max(1, $days) < 900000 ? 'budget' : ($has(['luxury']) ? 'luxury' : 'mid');
            $interests = collect($cfg['interests'])->keys()->filter(fn ($k) => str_contains($q, $k) || ($k === 'chimps' && str_contains($q, 'chimp')) || ($k === 'birds' && str_contains($q, 'bird')))->values()->all() ?: ['wildlife', 'waterfalls', 'boat'];
            $plan = $this->engine->build(['days' => $days, 'adults' => 1, 'category' => $cat, 'tier' => $tier, 'interests' => $interests, 'budget_limit' => $limit]);
            $text = "Here is a {$days}-day Murchison Falls plan for one {$cfg['categories'][$cat]['label']} traveller ({$cfg['tiers'][$tier]['label']} stay):\n";
            foreach ($plan['days'] as $day) {
                $acts = collect($day['items'])->where('kind', 'activity')->pluck('title')->implode(', ') ?: 'travel';
                $text .= "Day {$day['day']}: {$acts}\n";
            }
            $text .= 'Estimated total: '.ugx($plan['budget']['total'])." (≈US\${$plan['budget']['total_usd']})";
            $text .= $limit ? ($plan['budget']['total'] <= $limit ? ' — within your budget.' : ' — above your budget; try fewer days or the budget tier.') : '.';
            $text .= "\nOpen Smart Itinerary to adjust dates, group size and interests, then save or book it.";

            return [$text, 'itinerary', $plan];
        }
        if ($has(['chimp', 'budongo', 'kaniyo', 'primate'])) {
            return [$line($act('budongo-chimpanzee-trekking'))."\nIt runs in the morning in Budongo Forest (Kaniyo Pabidi), south of the river, so it fits best on your arrival or departure day. Minimum age 12. Wear long trousers and boots.", 'chimps', null];
        }
        if ($has(['shoebill', 'delta', 'bird', 'lake albert'])) {
            return [$line($act('albert-delta-shoebill-birding'))."\nEarly-morning launches give the best chance of seeing the shoebill in the papyrus where the Nile enters Lake Albert.", 'birding', null];
        }
        if ($has(['night'])) {
            return [$line($act('night-game-drive'))."\nNight drives run on the North Bank with a UWA ranger and spotlight — look for leopards, hyenas and bush babies. Book at the Paraa UWA office.", 'night', null];
        }
        if ($has(['fish', 'nile perch'])) {
            return [$line($act('sport-fishing'))."\nA UWA sport-fishing permit is required; fishing is catch-and-release below the falls.", 'fishing', null];
        }
        if ($has(['hike', 'top of the falls', 'baker', 'climb'])) {
            return [$line($act('hike-to-the-top-of-the-falls'))."\nGet dropped by the launch at the base of the falls and climb with a ranger to the top — about 45–90 minutes. Wear shoes with grip: the rocks are wet from the spray.", 'hike', null];
        }
        if ($has(['boat', 'cruise', 'launch', 'base of the falls', 'bottom of the falls', 'hippo', 'croc'])) {
            return [$line($act('victoria-nile-boat-cruise'))."\nLaunches leave Paraa Jetty in the morning and afternoon; check Travel Updates for today's schedule.", 'boat', null];
        }
        if ($has(['game drive', 'lion', 'elephant', 'giraffe', 'buligi', 'north bank', 'safari', 'wildlife'])) {
            return [$line($act('north-bank-game-drive'))."\nThe best game viewing is on the North Bank (Buligi tracks), reached by the Paraa ferry. Start at dawn and hire a UWA ranger guide.", 'game-drive', null];
        }
        if ($has(['ferry'])) {
            $f = TravelUpdate::current()->where('category', 'ferry')->first();

            return [($f ? "{$f->title}: {$f->body}" : 'The Paraa ferry carries vehicles across the Nile to the North Bank throughout the day.').' Ferry fee ≈UGX '.number_format($cfg['ferry_per_crossing_ugx']).' per vehicle per crossing.', 'ferry', null];
        }
        if ($has(['weather', 'season', 'best time', 'when to', 'rain', 'dry', 'month', 'climate'])) {
            return ["Murchison Falls is hot (25–32 °C by day). Main dry seasons: December–February and June–August — best for game drives. March–May and September–November are wetter: green, fewer visitors, excellent birding, but some northern tracks get muddy. See Travel Updates for the live 7-day forecast at Paraa.", 'weather', null];
        }
        if ($has(['pack', 'bring', 'wear', 'clothes'])) {
            return ['Packing list: passport/ID and yellow fever card, neutral-coloured long sleeves (tsetse flies), insect repellent, sunscreen, hat, sunglasses, binoculars, camera + spare batteries, light rain jacket, warm layer for dawn/night drives, hiking boots for Budongo and the hike to the top, malaria tablets, cash/mobile money.', 'packing', null];
        }
        if ($has(['rule', 'regulation', 'allowed', 'drone', 'speed', 'uwa'])) {
            return ["Key UWA rules in Murchison Falls: drive at most 40 km/h; stay in your vehicle on game drives except at designated points; no off-track driving; do not feed or disturb animals; keep 5+ m from wildlife (7–10 m for chimpanzees, no tracking if you are sick); no littering; drones need prior UWA permission; gates open 07:00–19:00; night drives only with a UWA ranger; fishing only with a permit.", 'rules', null];
        }
        if ($has(['fee', 'price', 'cost', 'how much', 'entry', 'budget', 'expens'])) {
            return ["UWA park entry per adult per day: US\${$cfg['entry']['fnr']} Foreign Non-Resident, US\${$cfg['entry']['fr']} Foreign Resident, UGX ".number_format($cfg['entry']['eac'])." East African Citizen (children about half).\n".$line($act('victoria-nile-boat-cruise'))."\n".$line($act('north-bank-game-drive'))."\nUse the Budget Calculator for a full estimate with accommodation and transport.", 'fees', null];
        }
        if ($has(['gate', 'get there', 'route', 'direction', 'distance', 'kampala', 'entebbe', 'masindi', 'airfield', 'fly'])) {
            return ['From Kampala it is ≈255 km (4.5 h) via Luweero and Masindi to Kichumbanyobo Gate, then 45 km to Paraa. Other gates: Bugungu (west, from Hoima/Butiaba), Tangi and Chobe (east, near Karuma on the Kampala–Gulu road) and Wankwar (north, from Gulu/Pakwach). Scheduled flights land at Pakuba Airfield on the North Bank. Open Smart Navigation for distances and travel times between any two points.', 'navigation', null];
        }
        if ($has(['stay', 'lodge', 'hotel', 'accommodation', 'camp', 'banda'])) {
            return [collect($cfg['tiers'])->map(fn ($t) => "• {$t['label']}: ≈US\${$t['usd_per_person_night']} per person per night — {$t['examples']}.")->implode("\n")."\nBook early for July–August and December.", 'accommodation', null];
        }
        if ($has(['360', 'virtual', 'vr', 'preview'])) {
            return ['Open Virtual Exploration to preview each activity in 360°. The panoramas were captured on an HP5 360 VR camera during MUBS field research. Tap a hotspot to read notes, play audio, or add that activity to your Smart Itinerary.', 'vr', null];
        }

        return ['I can help with Murchison Falls park fees (by UWA visitor category), the best time to visit, weather, packing, UWA rules, the Paraa ferry, gates and routes, and every activity: boat cruise, game drives, the hike to the top of the falls, chimpanzees, shoebill birding, fishing and night drives. Try: "Plan 3 days with $1,200" or "What should I pack?"', 'help', null];
    }
}
