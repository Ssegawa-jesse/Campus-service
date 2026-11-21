<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelUpdate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Live-editable operational alerts (Module 5). */
class TravelUpdateController extends Controller
{
    public function index()
    {
        return view('admin.travel-updates.index', ['updates' => TravelUpdate::with('author')->orderByDesc('is_active')->orderByDesc('is_pinned')->latest()->get()]);
    }

    public function create()
    {
        return view('admin.travel-updates.form', ['update' => new TravelUpdate(['is_active' => true, 'severity' => 'info', 'category' => 'ferry'])]);
    }

    public function store(Request $request)
    {
        TravelUpdate::create($this->data($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.travel-updates.index')->with('success', 'Travel update published.');
    }

    public function edit(TravelUpdate $travelUpdate)
    {
        return view('admin.travel-updates.form', ['update' => $travelUpdate]);
    }

    public function update(Request $request, TravelUpdate $travelUpdate)
    {
        $travelUpdate->update($this->data($request));

        return redirect()->route('admin.travel-updates.index')->with('success', 'Travel update saved.');
    }

    public function destroy(TravelUpdate $travelUpdate)
    {
        $travelUpdate->delete();

        return back()->with('success', 'Travel update deleted.');
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(TravelUpdate::CATEGORIES))],
            'severity' => ['required', Rule::in(array_keys(TravelUpdate::SEVERITIES))],
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:3000',
            'location' => 'nullable|string|max:120',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
        ]);

        return $data + ['is_active' => $request->boolean('is_active'), 'is_pinned' => $request->boolean('is_pinned')];
    }
}
