<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()->load('member')]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $member = $user->member;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{9,15}$/'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
        if ($member) {
            $rules += Member::profileRules(required: false);
        }

        $data = $request->validate($rules, Member::profileMessages());

        $user->update($request->only('name', 'email', 'phone'));
        $user->replacePhoto($request->file('photo'));
        $member?->update([
            'programme' => $data['programme'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'year_of_study' => $member->type === 'student' ? ($data['year_of_study'] ?? null) : null,
        ]);

        return back()->with('success', 'Profile updated.');
    }

    public function removePhoto(Request $request)
    {
        $request->user()->removePhoto();

        return back()->with('success', 'Profile picture removed.');
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $request->input('password')]);

        return back()->with('success', 'Password changed.');
    }
}
