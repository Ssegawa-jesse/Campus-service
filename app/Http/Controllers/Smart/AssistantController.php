<?php

namespace App\Http\Controllers\Smart;

use App\Http\Controllers\Controller;
use App\Services\Murchison\TravelAssistant;
use Illuminate\Http\Request;

/** MODULE 4 – AI Travel Assistant. */
class AssistantController extends Controller
{
    public function index(TravelAssistant $assistant)
    {
        return view('smart.assistant', ['aiEnabled' => $assistant->aiEnabled()]);
    }

    public function ask(Request $request, TravelAssistant $assistant)
    {
        $data = $request->validate([
            'question' => 'required|string|max:1000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'in:user,assistant',
            'history.*.content' => 'nullable|string|max:4000',
        ]);
        $r = $assistant->ask($data['question'], $data['history'] ?? [], $request->user()?->id);

        return response()->json([
            'answer' => $r['answer'],
            'source' => $r['source'],
            'intent' => $r['intent'],
            'plan_link' => $r['plan'] ? route('itinerary.index') : null,
        ]);
    }
}
