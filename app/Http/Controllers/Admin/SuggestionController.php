<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BookSuggestion;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuggestionController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.suggestions.index', [
            'suggestions' => BookSuggestion::with('user', 'book', 'responder')
                ->when($request->query('status', 'pending') !== 'all', fn ($q) => $q->where('status', $request->query('status', 'pending')))
                ->orderByDesc('votes_count')
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, BookSuggestion $suggestion, NotificationService $notifier)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(BookSuggestion::STATUSES))],
            'response' => ['nullable', 'string', 'max:1000'],
        ]);

        $suggestion->update($data + ['responded_by' => $request->user()->id]);
        AuditLog::record('suggestion.updated', "\"{$suggestion->title}\" marked {$suggestion->status}");

        $notifier->email(
            $suggestion->user,
            'Update on your book recommendation',
            "Hello {$suggestion->user->name}, your recommendation \"{$suggestion->title}\" is now: {$suggestion->statusLabel()}."
            .($suggestion->response ? " Librarian's note: {$suggestion->response}" : '')
        );

        return back()->with('success', 'Recommendation updated and the member has been notified.');
    }
}
