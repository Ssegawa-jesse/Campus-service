<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Operator;
use App\Models\Park;
use App\Models\Payment;
use App\Models\Scene;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\ViewLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));

        $trend = $months->map(fn ($m) => [
            'label' => $m->format('M y'),
            'views' => ViewLog::whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count(),
            'bookings' => Booking::whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count(),
            'revenue' => (int) Payment::where('status', 'successful')->whereBetween('paid_at', [$m, $m->copy()->endOfMonth()])->sum('amount'),
        ]);

        // Popular interests from all trip-planner submissions
        $interests = [];
        TripPlan::pluck('preferences')->each(function ($p) use (&$interests) {
            foreach ($p['interests'] ?? [] as $i) {
                $interests[$i] = ($interests[$i] ?? 0) + 1;
            }
        });
        arsort($interests);

        return view('admin.dashboard', [
            'stats' => [
                'tourists' => User::where('role', 'tourist')->count(),
                'operators' => Operator::count(),
                'pending_operators' => Operator::where('is_verified', false)->count(),
                'parks' => Park::count(),
                'scenes' => Scene::count(),
                'views' => ViewLog::count(),
                'bookings' => Booking::count(),
                'revenue' => (int) Payment::where('status', 'successful')->sum('amount'),
                'plans' => TripPlan::count(),
            ],
            'parkViews' => Park::orderByDesc('views_count')->get(['name', 'views_count']),
            'topScenes' => Scene::with('park:id,name')->orderByDesc('views_count')->take(8)->get(),
            'bookingsPerPark' => Park::withCount('bookings')->orderByDesc('bookings_count')->get(['id', 'name']),
            'statusBreakdown' => Booking::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'interests' => collect($interests)->mapWithKeys(fn ($v, $k) => [Park::INTERESTS[$k] ?? $k => $v]),
            'trend' => $trend,
            'recentBookings' => Booking::with('tourist', 'park', 'operator')->latest()->take(6)->get(),
        ]);
    }

    /** CSV report of park interest & bookings (open in Excel / SPSS). */
    public function export()
    {
        $parks = Park::withCount(['bookings', 'scenes', 'reviews'])->withAvg('reviews', 'rating')->orderBy('name')->get();

        return response()->streamDownload(function () use ($parks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Park', 'Region', 'Page views', '360 scenes', 'Bookings', 'Reviews', 'Average rating', 'Revenue (UGX)']);
            foreach ($parks as $p) {
                $revenue = Payment::where('status', 'successful')->whereHas('booking', fn ($q) => $q->where('park_id', $p->id))->sum('amount');
                fputcsv($out, [$p->name, $p->region, $p->views_count, $p->scenes_count, $p->bookings_count, $p->reviews_count, round((float) $p->reviews_avg_rating, 2), $revenue]);
            }
            fclose($out);
        }, 'pearl360-park-report-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
