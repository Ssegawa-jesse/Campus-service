<?php

namespace App\Services;

use App\Models\Park;
use Illuminate\Support\Collection;

/**
 * Knowledge-based (rule-based) recommender, as described in section 3.8.2 of the proposal.
 *
 * Every park is scored out of 100 against the tourist's answers:
 *   interests match ........ 40
 *   fitness vs difficulty .. 20
 *   budget ................. 15
 *   travel month (season) .. 15
 *   days available ......... 10
 * Parks are ranked by score, and each result carries human-readable reasons.
 */
class RecommendationService
{
    public const WEIGHTS = ['interests' => 40, 'fitness' => 20, 'budget' => 15, 'month' => 15, 'days' => 10];

    /**
     * @param  array{interests?: array, fitness?: int, budget?: float|int, days?: int, month?: int}  $prefs
     * @return Collection<int, array{park: Park, score: int, reasons: array, warnings: array, activities: Collection}>
     */
    public function recommend(array $prefs, ?Collection $parks = null, int $limit = 5): Collection
    {
        $parks ??= Park::published()->with('activities')->get();

        return $parks
            ->map(fn (Park $park) => $this->score($park, $prefs))
            ->sortByDesc('score')
            ->values()
            ->take($limit);
    }

    public function score(Park $park, array $prefs): array
    {
        $interests = array_values($prefs['interests'] ?? []);
        $fitness = (int) ($prefs['fitness'] ?? 2);
        $budget = (float) ($prefs['budget'] ?? 0);
        $days = (int) ($prefs['days'] ?? 0);
        $month = (int) ($prefs['month'] ?? 0);

        $reasons = [];
        $warnings = [];
        $score = 0.0;

        // 1. Interests
        $parkInterests = $park->interests ?? [];
        $matched = array_values(array_intersect($interests, $parkInterests));
        if ($interests) {
            $score += self::WEIGHTS['interests'] * (count($matched) / count($interests));
            if ($matched) {
                $reasons[] = 'Matches your interests: '.collect($matched)->map(fn ($i) => Park::INTERESTS[$i] ?? $i)->implode(', ');
            }
        } else {
            $score += self::WEIGHTS['interests'] / 2;
        }

        // 2. Fitness vs park difficulty
        $gap = $park->difficulty_level - $fitness;
        if ($gap <= 0) {
            $score += self::WEIGHTS['fitness'];
            $reasons[] = 'Suits your fitness level ('.$park->difficultyLabel().')';
        } elseif ($gap === 1) {
            $score += self::WEIGHTS['fitness'] / 2;
            $warnings[] = 'Some activities may be more demanding than your fitness level';
        } else {
            $warnings[] = 'This park is physically challenging';
        }

        // 3. Budget (USD, per person)
        if ($budget > 0) {
            if ($park->min_cost <= $budget) {
                $score += self::WEIGHTS['budget'];
                $reasons[] = 'Within your budget (from $'.number_format((float) $park->min_cost).')';
            } elseif ($park->min_cost <= $budget * 1.5) {
                $score += self::WEIGHTS['budget'] / 3;
                $warnings[] = 'Slightly above your budget (from $'.number_format((float) $park->min_cost).')';
            } else {
                $warnings[] = 'Above your budget (from $'.number_format((float) $park->min_cost).')';
            }
        } else {
            $score += self::WEIGHTS['budget'] / 2;
        }

        // 4. Travel month vs best season
        if ($month >= 1 && $month <= 12) {
            if (in_array($month, $park->best_months ?? [], true)) {
                $score += self::WEIGHTS['month'];
                $reasons[] = month_names()[$month].' is a great time to visit';
            } else {
                $score += self::WEIGHTS['month'] / 3;
                $warnings[] = month_names()[$month].' is rainy season here – expect muddy trails';
            }
        } else {
            $score += self::WEIGHTS['month'] / 2;
        }

        // 5. Days available vs minimum recommended stay (+ travel time from Kampala)
        if ($days > 0) {
            if ($days >= $park->min_days) {
                $score += self::WEIGHTS['days'];
                $reasons[] = "Fits in your {$days}-day trip";
            } else {
                $warnings[] = "Best with at least {$park->min_days} days";
            }
        } else {
            $score += self::WEIGHTS['days'] / 2;
        }

        // Suggested activities = those matching the tourist's interests and fitness.
        $activities = $park->activities
            ->filter(fn ($a) => $a->fitness_level <= max($fitness, 1) + 0)
            ->sortBy(fn ($a) => $this->activityMatchesInterests($a->category, $interests) ? 0 : 1)
            ->take(4)
            ->values();

        return [
            'park' => $park,
            'score' => (int) round($score),
            'reasons' => $reasons,
            'warnings' => $warnings,
            'activities' => $activities,
        ];
    }

    private function activityMatchesInterests(string $category, array $interests): bool
    {
        $map = [
            'primates' => ['gorillas', 'chimpanzees', 'primates'],
            'game_drive' => ['big_game'],
            'boat' => ['boat', 'waterfalls'],
            'hiking' => ['hiking', 'waterfalls'],
            'birding' => ['birding'],
            'culture' => ['culture'],
            'nature' => ['walking_safari', 'hot_springs', 'remote'],
        ];

        return (bool) array_intersect($map[$category] ?? [], $interests);
    }
}
