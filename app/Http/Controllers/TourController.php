<?php

namespace App\Http\Controllers;

use App\Models\Scene;
use App\Models\ViewLog;

class TourController extends Controller
{
    /** Called by the viewer (AJAX) each time a scene is opened – feeds the analytics dashboard. */
    public function logScene(Scene $scene)
    {
        ViewLog::record(scene: $scene);

        return response()->json(['ok' => true]);
    }
}
