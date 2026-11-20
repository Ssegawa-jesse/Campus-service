<?php

namespace Tests\Feature;

use App\Models\Park;
use App\Services\RecommendationService;
use Database\Seeders\ParkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_gorilla_lovers_get_gorilla_parks_first(): void
    {
        $this->seed(ParkSeeder::class);
        $results = app(RecommendationService::class)->recommend(['interests' => ['gorillas'], 'fitness' => 3, 'budget' => 1500, 'days' => 4, 'month' => 7]);

        $top2 = $results->take(2)->map(fn ($r) => $r['park']->slug)->all();
        $this->assertContains('bwindi-impenetrable-national-park', $top2);
        $this->assertContains('mgahinga-gorilla-national-park', $top2);
    }

    public function test_low_fitness_small_budget_prefers_easy_savannah_parks(): void
    {
        $this->seed(ParkSeeder::class);
        $top = app(RecommendationService::class)->recommend(['interests' => ['big_game', 'boat'], 'fitness' => 1, 'budget' => 150, 'days' => 2, 'month' => 1])->first();

        $this->assertSame(1, $top['park']->difficulty_level);
        $this->assertGreaterThanOrEqual(80, $top['score']);
    }

    public function test_planner_form_returns_results_page(): void
    {
        $this->seed(ParkSeeder::class);
        $this->post('/planner/results', ['interests' => ['hiking'], 'fitness' => 3, 'budget' => 500, 'days' => 5, 'month' => 8])
            ->assertOk()->assertSee('Rwenzori');
        $this->assertDatabaseCount('trip_plans', 1);
    }

    public function test_planner_requires_an_interest(): void
    {
        $this->post('/planner/results', ['fitness' => 2])->assertSessionHasErrors('interests');
    }
}
