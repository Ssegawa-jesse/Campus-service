<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Services\CirculationService;
use App\Services\NotificationService;
use App\Support\LibrarySettings;
use Illuminate\Console\Command;

class RunDailyLibraryTasks extends Command
{
    protected $signature = 'library:daily';

    protected $description = 'Send due-date reminders and overdue notices, update fines and expire uncollected reservations';

    public function handle(CirculationService $circulation, NotificationService $notifier): int
    {
        $library = LibrarySettings::get('library_name');
        $daysBefore = LibrarySettings::int('reminder_days_before');

        // Reminders before (and on) the due date.
        $dueSoon = Loan::active()
            ->whereNull('reminder_sent_at')
            ->where('due_at', '>=', now())
            ->where('due_at', '<=', now()->addDays($daysBefore)->endOfDay())
            ->with('member.user', 'copy.book')
            ->get();

        foreach ($dueSoon as $loan) {
            $user = $loan->member->user;
            $notifier->notify($user, 'Library book due soon',
                "Hello {$user->name}, \"{$loan->copy->book->title}\" is due on {$loan->due_at->format('d M Y')}. "
                ."Please return or renew it to avoid fines. - {$library}");
            $loan->update(['reminder_sent_at' => now()]);
        }

        // Overdue: update fines daily, send a notice once.
        $overdue = Loan::overdue()->with('member.user', 'copy.book')->get();
        foreach ($overdue as $loan) {
            $fine = $circulation->settleFine($loan);

            if (! $loan->overdue_notice_sent_at) {
                $user = $loan->member->user;
                $notifier->notify($user, 'Overdue library book',
                    "Hello {$user->name}, \"{$loan->copy->book->title}\" was due on {$loan->due_at->format('d M Y')} and is now overdue. "
                    .'A fine of UGX '.number_format(LibrarySettings::int('fine_per_day')).' per day applies (currently UGX '.number_format($fine?->amount ?? 0).'). '
                    ."Please return it as soon as possible. - {$library}");
                $loan->update(['overdue_notice_sent_at' => now()]);
            }
        }

        $expired = $circulation->expireReservations();

        $this->info("Reminders: {$dueSoon->count()}, overdue loans: {$overdue->count()}, expired holds: {$expired}");

        return self::SUCCESS;
    }
}
