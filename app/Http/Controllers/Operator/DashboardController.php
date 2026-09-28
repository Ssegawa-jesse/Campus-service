<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ViewLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $operator = $request->user()->operator;
        abort_unless($operator, 404);

        $bookings = $operator->bookings();
        $revenue = Payment::where('status', 'successful')->whereHas('booking', fn ($q) => $q->where('operator_id', $operator->id));

        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $monthly = $months->map(fn ($m) => [
            'label' => $m->format('M'),
            'enquiries' => (clone $bookings)->whereBetween('created_at', [$m, $m->copy()->endOfMonth()])->count(),
            'revenue' => (int) (clone $revenue)->whereBetween('paid_at', [$m, $m->copy()->endOfMonth()])->sum('amount'),
        ]);

        return view('operator.dashboard', [
            'operator' => $operator->load('parks'),
            'stats' => [
                'pending' => (clone $bookings)->where('status', 'pending')->count(),
                'total' => (clone $bookings)->count(),
                'confirmed' => (clone $bookings)->whereIn('status', ['paid', 'confirmed', 'completed'])->count(),
                'revenue' => (int) (clone $revenue)->sum('amount'),
                'packages' => $operator->packages()->count(),
                'package_views' => ViewLog::whereIn('tour_package_id', $operator->packages()->pluck('id'))->count(),
                'rating' => $operator->averageRating(),
            ],
            'recent' => (clone $bookings)->with('tourist', 'park')->latest()->take(6)->get(),
            'monthly' => $monthly,
        ]);
    }
}
