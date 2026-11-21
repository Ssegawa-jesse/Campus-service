<?php

namespace App\Http\Controllers\Smart;

use App\Http\Controllers\Controller;
use App\Models\TravelUpdate;

/** MODULE 5 – Travel Updates (live operational alerts, edited by the admin/researcher). */
class UpdatesController extends Controller
{
    public function index()
    {
        return view('smart.updates', [
            'updates' => TravelUpdate::current()->get()->groupBy('category'),
            'weather' => config('murchison.weather_point'),
        ]);
    }
}
