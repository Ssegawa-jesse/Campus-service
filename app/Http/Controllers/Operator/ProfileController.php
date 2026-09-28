<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Park;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('operator.profile', [
            'operator' => $request->user()->operator->load('parks'),
            'parks' => Park::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request)
    {
        $operator = $request->user()->operator;

        $data = $request->validate([
            'company_name' => 'required|string|max:150',
            'licence_no' => 'nullable|string|max:60',
            'contact_phone' => ['required', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'contact_email' => 'required|email|max:150',
            'website' => 'nullable|url|max:200',
            'address' => 'nullable|string|max:200',
            'about' => 'nullable|string|max:3000',
            'logo' => 'nullable|image|max:2048',
            'parks' => 'nullable|array',
            'parks.*' => 'exists:parks,id',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->storeUpload($request->file('logo'), 'operators', $operator->logo);
        }

        $operator->update(collect($data)->except('parks')->all());
        $operator->parks()->sync($data['parks'] ?? []);

        return back()->with('success', 'Company profile updated.');
    }
}
