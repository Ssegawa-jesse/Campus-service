<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Book;
use App\Models\Copy;
use App\Services\CirculationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CopyController extends Controller
{
    public function index(Book $book)
    {
        return view('admin.copies.index', [
            'book' => $book->load(['copies.currentLoan.member.user', 'authors']),
        ]);
    }

    public function store(Request $request, Book $book, CirculationService $circulation)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
            'condition' => ['required', Rule::in(Copy::CONDITIONS)],
        ]);

        for ($i = 0; $i < $data['quantity']; $i++) {
            $copy = $book->copies()->create([
                'code' => Copy::generateCode(),
                'shelf_location' => $data['shelf_location'] ?? null,
                'condition' => $data['condition'],
            ]);
            // A new copy can immediately serve the waiting list.
            $circulation->allocateToNextReservation($copy);
        }

        AuditLog::record('copy.created', "Added {$data['quantity']} copies of \"{$book->title}\"");

        return back()->with('success', "{$data['quantity']} copies added.");
    }

    public function update(Request $request, Copy $copy)
    {
        $data = $request->validate([
            'shelf_location' => ['nullable', 'string', 'max:100'],
            'condition' => ['required', Rule::in(Copy::CONDITIONS)],
            'status' => ['required', Rule::in(['available', 'lost', 'damaged', $copy->status])],
        ]);

        if (in_array($copy->status, ['on_loan', 'on_hold'], true) && $data['status'] !== $copy->status) {
            return back()->withErrors(['copy' => 'Return the copy or cancel its hold before changing its status.']);
        }

        $copy->update($data);
        AuditLog::record('copy.updated', "Copy {$copy->code}: {$copy->status}, {$copy->condition}");

        return back()->with('success', "Copy {$copy->code} updated.");
    }

    public function destroy(Copy $copy)
    {
        if (in_array($copy->status, ['on_loan', 'on_hold'], true)) {
            return back()->withErrors(['copy' => 'A copy on loan or on hold cannot be deleted.']);
        }

        AuditLog::record('copy.deleted', "Deleted copy {$copy->code} of \"{$copy->book->title}\"");
        $copy->delete();

        return back()->with('success', 'Copy deleted.');
    }

    /** Printable sheet of QR labels, for one title or for selected copies. */
    public function labels(Request $request)
    {
        $copies = Copy::with('book')
            ->when($request->query('book'), fn ($q, $id) => $q->where('book_id', $id))
            ->when($request->query('ids'), fn ($q, $ids) => $q->whereIn('id', explode(',', $ids)))
            ->orderBy('book_id')
            ->get();

        return view('admin.copies.labels', compact('copies'));
    }
}
