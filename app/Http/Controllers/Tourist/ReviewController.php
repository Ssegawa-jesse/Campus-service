<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'park_id' => 'required_without:operator_id|nullable|exists:parks,id',
            'operator_id' => 'required_without:park_id|nullable|exists:operators,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|min:10|max:2000',
        ]);

        Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'park_id' => $data['park_id'] ?? null, 'operator_id' => $data['operator_id'] ?? null],
            ['rating' => $data['rating'], 'comment' => $data['comment'], 'is_approved' => (bool) setting('auto_approve_reviews', '1')],
        );

        return back()->with('success', 'Thank you for your review!');
    }
}
