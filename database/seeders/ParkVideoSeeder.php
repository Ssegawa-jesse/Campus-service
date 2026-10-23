<?php

namespace Database\Seeders;

use App\Models\Park;
use Illuminate\Database\Seeder;

/**
 * Real YouTube videos for every park (feature video + one extra in the gallery).
 * Safe to run on an existing database – it never deletes anything:
 *   php artisan db:seed --class=ParkVideoSeeder
 * Replace or add videos any time in Admin → Parks → Photos & videos.
 */
class ParkVideoSeeder extends Seeder
{
    public const VIDEOS = [
        'bwindi-impenetrable-national-park' => [
            ['ByvHSqXkWGU', "Exploring Uganda's Bwindi Impenetrable National Park"],
            ['Ytqpwa8bzYU', 'Gorilla trekking experience in Bwindi Impenetrable National Park'],
        ],
        'mgahinga-gorilla-national-park' => [
            ['z6y0MRH8YOw', 'Golden monkey tracking in Mgahinga Gorilla National Park'],
            ['0tRENBHnZSc', 'Golden monkeys of Mgahinga Gorilla National Park'],
        ],
        'queen-elizabeth-national-park' => [
            ['8_dxZf_07so', 'Queen Elizabeth National Park & Kazinga Channel safari'],
            ['nhcifRkELH8', 'Tree-climbing lions of Ishasha, Queen Elizabeth National Park'],
        ],
        'murchison-falls-national-park' => [
            ['noIT-TWOEZY', "Murchison Falls – Uganda's largest national park"],
            ['F6dx1ziHqpc', 'Safari guide to Murchison Falls: Big Five, Nile cruise and hidden gems'],
        ],
        'kidepo-valley-national-park' => [
            ['rwnbF03yfNw', 'Explore Kidepo Valley National Park'],
            ['zXoq6vMn-RU', 'A game drive through Kidepo Valley National Park'],
        ],
        'lake-mburo-national-park' => [
            ['SXH4wulAAFU', 'Lake Mburo National Park, Uganda'],
            ['UcZUYeuAlXA', 'UWA translocates zebras and topis from Lake Mburo National Park'],
        ],
        'rwenzori-mountains-national-park' => [
            ['jCumpUa7G5I', "Wonders of the Rwenzori – Uganda's Mountains of the Moon"],
            ['XMKwqc5-fwI', 'Rwenzori: climbing Margherita Peak (5,109 m)'],
        ],
        'kibale-national-park' => [
            ['0TP8MFX4720', 'Chimpanzee tracking at Kibale National Park'],
            ['srZTp2XhYcw', 'Kibale National Park – chimpanzee tracking & habituation'],
        ],
        'semuliki-national-park' => [
            ['jmdLdYPcJVA', 'The Sempaya hot springs of Semuliki National Park'],
            ['p2YvMGIo26w', 'Semuliki National Park, Uganda'],
        ],
        'mount-elgon-national-park' => [
            ['FmEMZYdXzPA', 'Explore Mount Elgon National Park'],
            ['Kuiu_V32Hec', 'Sipi Falls on the slopes of Mount Elgon'],
        ],
    ];

    public function run(): void
    {
        foreach (self::VIDEOS as $slug => $videos) {
            $park = Park::where('slug', $slug)->first();
            if (! $park) {
                continue;
            }

            [$featureId] = $videos[0];
            // Only fill an empty feature video or replace a YouTube link – never an uploaded MP4.
            if (! $park->video_url || youtube_embed($park->video_url)) {
                $park->update(['video_url' => 'https://www.youtube.com/watch?v='.$featureId]);
            }

            $order = (int) $park->media()->max('sort_order');
            foreach ($videos as [$id, $caption]) {
                $url = 'https://www.youtube.com/watch?v='.$id;
                if ($id === $featureId || $park->media()->where('path', $url)->exists()) {
                    continue;
                }
                $park->media()->create(['type' => 'youtube', 'path' => $url, 'caption' => $caption, 'credit' => 'YouTube', 'sort_order' => ++$order]);
            }
        }
    }
}
