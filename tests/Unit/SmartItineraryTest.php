<?php

namespace Tests\Unit;

use App\Services\Murchison\SmartItinerary;
use App\Services\Murchison\TravelAssistant;
use App\Services\Murchison\TripCost;
use Tests\TestCase;

class SmartItineraryTest extends TestCase
{
    private function plan(array $over = []): array
    {
        return (new SmartItinerary())->build(array_merge(SmartItinerary::defaults(), ['start_date' => '2026-12-10'], $over));
    }

    private function activityIds(array $p): array
    {
        return collect($p['days'])->flatMap(fn ($d) => $d['items'])->where('kind', 'activity')->pluck('activity_id')->all();
    }

    public function test_a_three_day_plan_starts_and_ends_with_travel(): void
    {
        $p = $this->plan();
        $this->assertCount(3, $p['days']);
        $this->assertSame('travel', $p['days'][0]['items'][0]['kind']);
        $this->assertTrue(collect($p['days'][2]['items'])->contains(fn ($i) => $i['kind'] === 'travel' && str_starts_with($i['title'], 'Drive back')));
        $this->assertSame(2, collect($p['days'])->filter(fn ($d) => collect($d['items'])->contains('kind', 'stay'))->count());
    }

    public function test_wildlife_and_boat_lovers_get_a_dawn_game_drive_and_a_launch_trip(): void
    {
        $items = collect($this->plan()['days'])->flatMap(fn ($d) => $d['items']);
        $this->assertSame('06:30', $items->firstWhere('activity_id', 'game-drive')['time']);
        $this->assertNotNull($items->firstWhere('activity_id', 'launch'));
    }

    public function test_activities_above_the_fitness_level_are_left_out_with_a_reason(): void
    {
        $p = $this->plan(['fitness' => 1, 'interests' => ['chimps', 'hiking']]);
        $this->assertNotContains('chimps', $this->activityIds($p));
        $this->assertTrue(collect($p['skipped'])->contains(fn ($s) => str_starts_with($s, 'Chimpanzee tracking')));
    }

    public function test_special_extras_only_appear_when_their_interest_is_chosen(): void
    {
        $this->assertNotContains('balloon', $this->activityIds($this->plan()));
        $this->assertContains('balloon', $this->activityIds($this->plan(['interests' => ['wildlife', 'waterfalls', 'boat', 'adventure']])));
    }

    public function test_a_must_do_activity_is_scheduled(): void
    {
        $p = $this->plan(['interests' => ['waterfalls'], 'must_do' => ['delta']]);
        $this->assertContains('delta', $this->activityIds($p));
        $this->assertTrue(collect($p['reasons'])->contains(fn ($r) => str_contains($r, 'you asked for it')));
    }

    public function test_relaxed_pace_has_at_most_two_activities_a_day(): void
    {
        $p = $this->plan(['days' => 4, 'pace' => 'relaxed', 'interests' => ['wildlife', 'waterfalls', 'boat', 'birds', 'chimps', 'hiking'], 'fitness' => 3]);
        foreach ($p['days'] as $d) {
            $this->assertLessThanOrEqual(2, collect($d['items'])->where('kind', 'activity')->count(), $d['title']);
        }
    }

    public function test_no_two_activities_share_a_start_time(): void
    {
        foreach ([2, 3, 4, 5, 6] as $days) {
            $p = $this->plan(['days' => $days, 'interests' => ['wildlife', 'waterfalls', 'boat', 'birds', 'chimps', 'hiking', 'culture'], 'fitness' => 3]);
            foreach ($p['days'] as $d) {
                $times = collect($d['items'])->where('kind', 'activity')->pluck('time');
                $this->assertSame($times->count(), $times->unique()->count(), "{$days}-day plan, {$d['title']}");
            }
        }
    }

    public function test_rainy_season_and_long_day_trips_carry_warnings(): void
    {
        $wet = $this->plan(['start_date' => '2026-04-15']);
        $this->assertSame('Wet', $wet['season']['season']);
        $this->assertTrue(collect($wet['warnings'])->contains(fn ($w) => str_contains($w, 'rainy season')));
        $this->assertTrue(collect($this->plan(['days' => 1])['warnings'])->contains(fn ($w) => str_contains($w, 'one-day visit')));
    }

    public function test_budget_tier_ranks_expensive_extras_lower(): void
    {
        $p = $this->plan(['tier' => 'budget', 'residency' => 'fr', 'interests' => ['wildlife', 'waterfalls', 'chimps', 'hiking'], 'pace' => 'relaxed', 'origin' => 'entebbe', 'adults' => 4, 'children' => 2]);
        $this->assertNotContains('chimps', $this->activityIds($p));
    }

    public function test_park_entry_is_per_adult_per_day_with_children_at_half_price(): void
    {
        $b = TripCost::compute(['days' => 2, 'adults' => 2, 'children' => 2, 'residency' => 'fnr', 'tier' => 'budget', 'transport' => 'arranged', 'origin' => 'kampala', 'activity_ids' => []]);
        $this->assertEquals(45 * 3700 * 3 * 2, $b['lines'][0]['ugx']);
    }

    public function test_east_african_citizens_pay_in_shillings_and_children_are_not_charged_for_chimps(): void
    {
        $this->assertEquals(30000, TripCost::activityCost('launch', 1, 0, 'eac'));
        $this->assertEquals(TripCost::activityCost('chimps', 2, 0, 'eac'), TripCost::activityCost('chimps', 2, 3, 'eac'));
    }

    public function test_the_planner_total_matches_the_cost_rules(): void
    {
        $p = $this->plan();
        $this->assertEqualsWithDelta(array_sum(array_column(array_slice($p['costs'], 0, -1), 'ugx')) * 1.1, $p['total'], 1);
    }

    public function test_share_codes_round_trip_and_bad_codes_are_rejected(): void
    {
        $p = $this->plan(['days' => 4, 'must_do' => ['delta']]);
        $this->assertSame($p['input'], SmartItinerary::decode($p['code']));
        $this->assertNull(SmartItinerary::decode('not-a-plan'));
        $this->assertSame(6, SmartItinerary::normalize(['days' => 99])['days']);
    }

    public function test_the_built_in_assistant_answers_from_the_park_data(): void
    {
        $a = new TravelAssistant();
        $this->assertStringContainsString('Launch trip', $a->guideAnswer('How much is the boat trip?'));
        $this->assertStringContainsString('under 12', $a->guideAnswer('Can children track chimpanzees?'));
        $this->assertStringContainsStringIgnoringCase('shoebill', $a->guideAnswer('Where can I see the shoebill?'));
        $this->assertStringContainsString('dry seasons', $a->guideAnswer('When is the best time to visit?'));
        $this->assertStringContainsString('I can help', $a->guideAnswer('hello'));
        $this->assertStringContainsString('PARK ENTRY', $a->knowledge());
    }
}
