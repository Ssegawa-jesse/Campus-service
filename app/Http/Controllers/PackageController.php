<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use App\Models\Park;
use App\Models\TourPackage;
use App\Models\ViewLog;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request)
    {
        $query = TourPackage::with('park', 'operator')->where('is_active', true)
            ->whereHas('operator', fn ($q) => $q->where('is_verified', true));

        if ($park = $request->input('park')) {
            $query->whereHas('park', fn ($q) => $q->where('slug', $park));
        }
        if ($max = $request->integer('max_price')) {
            $query->where('price_ugx', '<=', $max);
        }
        if ($days = $request->integer('days')) {
            $query->where('days', '<=', $days);
        }

        return view('packages.index', [
            'packages' => $query->orderBy('price_ugx')->paginate(12)->withQueryString(),
            'parks' => Park::published()->orderBy('name')->get(['name', 'slug']),
        ]);
    }

    public function show(TourPackage $package)
    {
        abort_unless($package->is_active && $package->operator->is_verified, 404);
        ViewLog::record(package: $package);

        return view('packages.show', [
            'package' => $package->load('park', 'operator'),
            'more' => TourPackage::with('park')->where('operator_id', $package->operator_id)->whereKeyNot($package->id)->where('is_active', true)->take(3)->get(),
        ]);
    }

    public function operator(Operator $operator)
    {
        abort_unless($operator->is_verified, 404);

        return view('packages.operator', [
            'operator' => $operator->load(['parks', 'packages' => fn ($q) => $q->where('is_active', true)->with('park'),
                'reviews' => fn ($q) => $q->where('is_approved', true)->with('user')->latest()]),
        ]);
    }
}
