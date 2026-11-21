<?php

namespace App\Services\Murchison;

use Anthropic\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI travel assistant. Uses Claude when ANTHROPIC_API_KEY is set in .env; otherwise (or if the AI service
 * is unavailable) it answers common questions from the park information in config/murchison.php.
 */
class TravelAssistant
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function answer(array $messages): array
    {
        $last = end($messages)['content'] ?? '';
        $key = config('services.anthropic.key');
        if (! $key) {
            return ['reply' => $this->guideAnswer($last), 'source' => 'guide'];
        }

        try {
            $client = new Client(apiKey: $key);
            $response = $client->beta->messages->create(
                maxTokens: 4000,
                messages: $messages,
                model: 'claude-opus-5-5',
                betas: ['server-side-fallback-2026-07-01'],
                fallbacks: 'default',
                outputConfig: ['effort' => 'low'],
                system: [['type' => 'text', 'text' => $this->systemPrompt(), 'cacheControl' => ['type' => 'ephemeral']]],
            );
            if ($response->stopReason === 'refusal') {
                return ['reply' => $this->guideAnswer($last), 'source' => 'guide'];
            }
            $text = trim(collect($response->content)->filter(fn ($b) => ($b->type ?? null) === 'text')->map(fn ($b) => $b->text)->implode("\n"));

            return $text !== '' ? ['reply' => $text, 'source' => 'ai'] : ['reply' => $this->guideAnswer($last), 'source' => 'guide'];
        } catch (\Throwable $e) {
            Log::warning('Travel assistant AI error: '.$e->getMessage());

            return ['reply' => $this->guideAnswer($last), 'source' => 'guide'];
        }
    }

    public function systemPrompt(): string
    {
        return "You are the travel assistant of the Murchison Falls Smart Tourism Platform, a trip-planning website for Murchison Falls National Park in Uganda.\n"
            ."Answer visitors' questions about the park and recommend activities that suit them. Keep answers short and practical (under about 150 words), in plain English, using short paragraphs or bullet points.\n"
            ."Base prices, distances and times only on the park information below. Say that fees are estimates to be confirmed with the Uganda Wildlife Authority (UWA). If you do not know something, say so and suggest contacting UWA.\n"
            ."When a visitor wants a full plan, cost estimate, map or forecast, point them to the Smart itinerary, Travel budget, Map & routes or Travel updates pages of this website.\n"
            ."Only help with travel to Murchison Falls and Uganda; politely decline unrelated requests.\n\nPARK INFORMATION\n".$this->knowledge();
    }

    /** Park facts given to the AI model so its answers stay grounded in this system's data. */
    public function knowledge(): string
    {
        $c = config('murchison');
        $lines = ["PARK: {$c['name']}. {$c['summary']} Managed by the Uganda Wildlife Authority (UWA)."];
        $e = $c['fees']['entry_per_day'];
        $lines[] = "PARK ENTRY per adult per 24 h: US\${$e['fnr']} (foreign non-resident), US\${$e['fr']} (foreign resident), UGX ".number_format($e['eac']).' (East African citizen). Children pay about half. Vehicle entry about UGX '.number_format($c['fees']['vehicle_entry_per_day_ugx']).' a day; Paraa ferry about UGX '.number_format($c['fees']['ferry_per_vehicle_ugx']).' per vehicle per crossing.';
        $lines[] = 'ACTIVITIES:';
        foreach ($c['activities'] as $a) {
            $lines[] = "- {$a['name']}: {$a['description']} Duration about {$a['hours']} h. Fee per person: US\${$a['fee']['fnr']} (foreign non-resident), US\${$a['fee']['fr']} (foreign resident), UGX ".number_format($a['fee']['eac']).' (East African citizen)'
                .(isset($a['group_fee']) ? "; plus a guide fee of about US\${$a['group_fee']['fnr']} per group" : '').(isset($a['wet_note']) ? " Rainy season: {$a['wet_note']}" : '');
        }
        $lines[] = 'PLACES:';
        foreach ($c['attractions'] as $p) {
            $lines[] = "- {$p['name']} ({$p['category']}): {$p['description']}".($p['tip'] ? " Tip: {$p['tip']}" : '');
        }
        $lines[] = 'GETTING THERE: '.collect($c['origins'])->map(fn ($o) => "from {$o['label']} about {$o['km_to_gate']} km ({$o['hours_to_gate']} h) to Kichumbanyobo Gate")->implode('; ').'; then about 45 km to Paraa.';
        $lines[] = 'ACCOMMODATION (per person per night): '.collect($c['tiers'])->map(fn ($t) => "{$t['label']} about US\${$t['per_person_night_usd']} — {$t['examples']}")->implode('; ').'.';
        $lines[] = 'SEASONS: '.collect($c['months'])->map(fn ($m) => "{$m[0]}: {$m[1]} ({$m[2]})")->implode(' ');
        $lines[] = 'TRAVEL NOTICES: '.collect($c['notices'])->map(fn ($n) => "{$n[0]}: {$n[1]}")->implode(' ');
        $lines[] = 'Exchange rate used: US$1 = UGX '.number_format($c['ugx_per_usd']).'. All prices are planning estimates to be confirmed with UWA.';

        return implode("\n", $lines);
    }

    /** Built-in answers from the park data, used when no AI key is configured or the AI is unavailable. */
    public function guideAnswer(string $question): string
    {
        $q = Str::lower($question);
        $has = fn (array $words) => Str::contains($q, $words);
        $c = config('murchison');
        $line = function (string $id) use ($c) {
            $a = $c['activities'][$id];

            return "• {$a['name']} — about {$a['hours']} h, US\${$a['fee']['fnr']} per person (foreign non-resident) / UGX ".number_format($a['fee']['eac'])." (East African citizen). {$a['description']}";
        };
        $e = $c['fees']['entry_per_day'];
        $o = $c['origins']['kampala'];

        return match (true) {
            $has(['chimp', 'primate', 'budongo', 'kaniyo', 'monkey']) => $line('chimps')."\n\nIt takes place in the morning in Budongo forest (Kaniyo Pabidi), in the south of the park, so it fits best on your arrival or departure day. Children under 12 cannot track chimpanzees. Wear long trousers and boots.",
            $has(['shoebill', 'bird', 'delta', 'lake albert']) => $line('delta')."\n\nMorning cruises give the best chance of seeing the shoebill. A guided birding walk around Paraa is also available.",
            $has(['boat', 'launch', 'cruise', 'bottom of the falls', 'hippo', 'crocodile']) => $line('launch')."\n\nTrips leave from the Paraa jetty in the morning and afternoon. If you enjoy walking, you can get off at the foot of the falls and hike to the top with a ranger (arrange in advance).",
            $has(['game drive', 'lion', 'elephant', 'giraffe', 'animal', 'safari', 'wildlife', 'buffalo', 'buligi']) => $line('game-drive')."\n\nThe best game viewing is on the northern bank (Buligi tracks), reached by the Paraa ferry. Start at dawn, when lions are most active, and take a UWA ranger guide.",
            $has(['fish']) => $line('fishing')."\n\nA fishing permit is required and fishing is catch-and-release.",
            $has(['balloon']) => $line('balloon')."\n\nAvailability is limited, so book well in advance.",
            $has(['rhino', 'ziwa']) => $line('ziwa-rhinos')."\n\nZiwa is on the Kampala–Masindi road, so it is easy to visit on the way to or from the park.",
            $has(['top of the falls', 'waterfall', 'the falls', 'gorge', 'hike']) => 'At the Top of the Falls the whole Nile squeezes through a narrow gorge before dropping. Trails lead to several viewpoints (about 2 hours). It is on the way in from the southern gate, so it fits well on your first afternoon. Late afternoon light is best for photos.',
            $has(['fee', 'price', 'cost', 'how much', 'entry', 'charge', 'pay', 'money', 'budget', 'expens']) => "Park entry per adult per day is about US\${$e['fnr']} for foreign non-residents, US\${$e['fr']} for foreign residents and UGX ".number_format($e['eac'])." for East African citizens (children about half).\n\nPopular activities:\n".$line('launch')."\n".$line('game-drive')."\n".$line('chimps')."\n\nFor a full estimate including accommodation and transport, use the Travel budget page. These are planning figures — confirm current UWA fees before paying.",
            $has(['when', 'best time', 'season', 'month', 'rain', 'dry', 'weather', 'climate']) => "The main dry seasons are December–February and June–August, the best times for game drives. March–May and September–November are rainier: the park is green, there are fewer visitors and birding is excellent, but some tracks can be muddy.\n\nSee the Travel updates page for the live 16-day forecast at Paraa.",
            $has(['get there', 'route', 'direction', 'drive', 'distance', 'far', 'kampala', 'entebbe', 'masindi', 'road', 'transport', 'how do i get']) => "From Kampala it is about {$o['km_to_gate']} km ({$o['hours_to_gate']} hours) via Luweero and Masindi to Kichumbanyobo Gate, then about 45 km to Paraa. From Entebbe add about an hour. Many visitors stop at Ziwa Rhino Sanctuary on the way. The Map & routes page shows every attraction and gives directions.",
            $has(['stay', 'lodge', 'hotel', 'accommodation', 'sleep', 'camp', 'banda']) => collect($c['tiers'])->map(fn ($t) => "• {$t['label']}: about US\${$t['per_person_night_usd']} per person per night — {$t['examples']}.")->implode("\n")."\n\nBook early for July–August and December.",
            $has(['pack', 'bring', 'wear', 'clothes', 'carry']) => 'Bring: passport and yellow fever certificate, sunscreen, hat, insect repellent, light-coloured long sleeves (tsetse flies), binoculars, a camera, a light jacket for boat trips and early drives, hiking boots for forest walks, a rain jacket in the rainy months, and some cash or mobile money.',
            $has(['safe', 'malaria', 'tsetse', 'yellow fever', 'vaccin', 'health', 'danger']) => collect($c['notices'])->map(fn ($n) => "• {$n[0]}: {$n[1]}")->implode("\n")."\n\nAlways follow ranger instructions and stay in your vehicle on game drives unless told otherwise.",
            $has(['ferry']) => 'The Paraa ferry carries vehicles across the Nile to the northern bank for game drives. It runs at set times during the day, so check the schedule at the gate and catch an early crossing for a dawn game drive.',
            $has(['how many days', 'how long', 'itinerary', 'plan', 'schedule', 'days']) => 'We recommend at least 3 days (2 nights) from Kampala: day 1 drive in and visit the Top of the Falls; day 2 a dawn game drive and an afternoon launch trip; day 3 a morning activity (delta cruise or chimpanzee tracking) and drive back. The Smart itinerary page builds a timed plan for your own dates and interests.',
            $has(['book', 'operator', 'tour company']) => 'You can book an approved tour operator on the Packages page, or press "Book this itinerary" after building your smart itinerary. The operator replies with a quote by SMS, and you can pay by MTN Mobile Money, Airtel Money or card.',
            $has(['recommend', 'what to do', 'activities', 'things to do', 'suggest', 'see']) => 'The highlights of Murchison Falls are:'."\n".collect(['launch', 'game-drive', 'top-falls', 'delta', 'chimps'])->map($line)->implode("\n")."\n\nTell the Smart itinerary page what you like and it will choose and time them for you.",
            default => 'I can help with park fees, the best time to visit, getting there, activities (game drives, boat trips, chimpanzees, the shoebill), accommodation, booking, what to pack and safety. Try asking, for example, "How much is the boat trip?" or "How many days do I need?"',
        };
    }
}
