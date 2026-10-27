<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookSuggestion;
use App\Models\Category;
use App\Models\Copy;
use App\Models\DigitalResource;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data so the system can be explored straight away. All passwords are "password".
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create(['name' => 'Head Librarian', 'email' => 'admin@mubs.ac.ug', 'phone' => '0700000001', 'password' => 'password', 'role' => 'admin']);
        $librarian = User::create(['name' => 'Circulation Desk', 'email' => 'librarian@mubs.ac.ug', 'phone' => '0700000002', 'password' => 'password', 'role' => 'librarian']);

        $people = [
            ['Wamala Adrian', 'student@mubs.ac.ug', '0772000001', '2400720657', 'student', 'Bachelor of Business Computing', '2004-03-14', 2],
            ['Namiiro Bronia', 'bronia@mubs.ac.ug', '0772000002', '2400718586', 'student', 'Bachelor of Business Computing', '2005-07-22', 2],
            ['Ainembabazi Agatha', 'agatha@mubs.ac.ug', '0772000003', '2400702953', 'student', 'Bachelor of Commerce', '2003-11-02', 3],
            ['Katusabe Brenda', 'brenda@mubs.ac.ug', '0772000004', '2400705499', 'student', 'Bachelor of Procurement and Supply Chain Management', '2006-01-30', 1],
            ['Dr. Okello James', 'lecturer@mubs.ac.ug', '0772000005', 'STF0001', 'staff', 'Faculty of Computing and Informatics', '1982-05-18', null],
        ];
        $members = collect($people)->map(function ($p) {
            $user = User::create(['name' => $p[0], 'email' => $p[1], 'phone' => $p[2], 'password' => 'password', 'role' => 'member']);

            return $user->member()->create([
                'member_no' => $p[3], 'type' => $p[4], 'programme' => $p[5],
                'date_of_birth' => $p[6], 'year_of_study' => $p[7], 'expiry_date' => now()->addYear(),
            ]);
        });

        $categories = collect(['Accounting & Finance', 'Business Computing', 'Management', 'Marketing', 'Economics', 'Procurement & Logistics', 'Law', 'Research Methods'])
            ->mapWithKeys(fn ($name) => [$name => Category::create(['name' => $name])]);

        $books = [
            ['Financial Accounting', 'Frank Wood, Alan Sangster', '9781292365435', 'Pearson', 2021, '15th', 'Accounting & Finance', 'A1', 4],
            ['Management Accounting', 'Colin Drury', '9781473773271', 'Cengage', 2021, '11th', 'Accounting & Finance', 'A2', 2],
            ['Corporate Finance', 'Stephen Ross, Randolph Westerfield, Jeffrey Jaffe', '9781265533199', 'McGraw-Hill', 2022, '13th', 'Accounting & Finance', 'A3', 2],
            ['Database System Concepts', 'Abraham Silberschatz, Henry Korth, S. Sudarshan', '9780078022159', 'McGraw-Hill', 2019, '7th', 'Business Computing', 'C1', 3],
            ['Software Engineering', 'Ian Sommerville', '9780133943030', 'Pearson', 2016, '10th', 'Business Computing', 'C2', 2],
            ['Systems Analysis and Design', 'Alan Dennis, Barbara Wixom, Roberta Roth', '9781119496489', 'Wiley', 2018, '7th', 'Business Computing', 'C3', 1],
            ['Laravel: Up & Running', 'Matt Stauffer', '9781098153267', "O'Reilly", 2023, '3rd', 'Business Computing', 'C4', 1],
            ['Management Information Systems', 'Kenneth Laudon, Jane Laudon', '9780136971276', 'Pearson', 2020, '16th', 'Business Computing', 'C5', 3],
            ['Principles of Management', 'Charles Hill, Steven McShane', '9780073530123', 'McGraw-Hill', 2008, '1st', 'Management', 'M1', 2],
            ['Human Resource Management', 'Gary Dessler', '9780135174692', 'Pearson', 2019, '16th', 'Management', 'M2', 2],
            ['Principles of Marketing', 'Philip Kotler, Gary Armstrong', '9781292341132', 'Pearson', 2020, '18th', 'Marketing', 'K1', 3],
            ['Economics', 'Paul Samuelson, William Nordhaus', '9780073511290', 'McGraw-Hill', 2009, '19th', 'Economics', 'E1', 2],
            ['Purchasing and Supply Chain Management', 'Kenneth Lysons, Brian Farrington', '9781292086590', 'Pearson', 2016, '9th', 'Procurement & Logistics', 'P1', 2],
            ['Business Law', 'James Marson, Katy Ferris', '9780198855170', 'Oxford University Press', 2020, '6th', 'Law', 'L1', 1],
            ['Research Design: Qualitative, Quantitative, and Mixed Methods Approaches', 'John W. Creswell', '9781452226101', 'SAGE', 2014, '4th', 'Research Methods', 'R1', 2],
            ['Research Methods for Business Students', 'Mark Saunders, Philip Lewis, Adrian Thornhill', '9781292208787', 'Pearson', 2019, '8th', 'Research Methods', 'R2', 0],
        ];

        $created = [];
        foreach ($books as [$title, $authors, $isbn, $publisher, $year, $edition, $category, $shelf, $copies]) {
            $book = Book::create([
                'title' => $title, 'isbn' => $isbn, 'publisher' => $publisher, 'year' => $year, 'edition' => $edition,
                'category_id' => $categories[$category]->id, 'subject' => $category,
                'description' => "{$title} ({$edition} edition) — a core text for {$category} courses at MUBS.",
            ]);
            $cover = database_path("seeders/covers/{$isbn}.jpg");
            if (is_file($cover)) {
                Storage::disk('public')->put("covers/{$isbn}.jpg", file_get_contents($cover));
                $book->update(['cover_image' => "covers/{$isbn}.jpg"]);
            }
            $book->authors()->sync(collect(explode(',', $authors))->map(fn ($a) => Author::firstOrCreate(['name' => trim($a)])->id));
            for ($i = 1; $i <= $copies; $i++) {
                $book->copies()->create(['code' => sprintf('BK-%03d-%02d', $book->id, $i), 'shelf_location' => "Shelf {$shelf}", 'condition' => 'good']);
            }
            $created[$title] = $book;
        }

        // Loans: one current, one due soon, one overdue (with fine), one returned late (paid fine).
        $loan = fn (Member $m, string $title, int $issuedDaysAgo, int $loanDays, ?int $returnedDaysAgo = null) => tap(Loan::create([
            'copy_id' => ($copy = $created[$title]->copies()->where('status', 'available')->first())->id,
            'member_id' => $m->id,
            'issued_by' => $librarian->id,
            'issued_at' => now()->subDays($issuedDaysAgo),
            'due_at' => now()->subDays($issuedDaysAgo)->addDays($loanDays)->endOfDay(),
            'returned_at' => $returnedDaysAgo !== null ? now()->subDays($returnedDaysAgo) : null,
        ]), fn () => $returnedDaysAgo === null && $copy->update(['status' => 'on_loan']));

        $loan($members[0], 'Database System Concepts', 3, 14);
        $loan($members[0], 'Software Engineering', 13, 14);
        $overdue = $loan($members[1], 'Financial Accounting', 20, 14);
        Fine::create(['loan_id' => $overdue->id, 'member_id' => $members[1]->id, 'amount' => $overdue->calculateFine(), 'status' => 'unpaid']);
        $late = $loan($members[2], 'Principles of Marketing', 40, 14, 22);
        $fine = Fine::create(['loan_id' => $late->id, 'member_id' => $members[2]->id, 'amount' => $late->calculateFine(), 'status' => 'unpaid']);
        $loan($members[4], 'Systems Analysis and Design', 5, 30);   // only copy: now unavailable
        $loan($members[3], 'Laravel: Up & Running', 2, 14);         // only copy: now unavailable
        $loan($members[2], 'Business Law', 6, 14);                  // only copy: now unavailable

        Reservation::create(['book_id' => $created['Systems Analysis and Design']->id, 'member_id' => $members[0]->id, 'status' => 'waiting']);
        Reservation::create(['book_id' => $created['Laravel: Up & Running']->id, 'member_id' => $members[1]->id, 'status' => 'waiting']);

        // Recommendations, visible to everyone.
        $suggestions = [
            [$members[0], null, 'Clean Code', 'Robert C. Martin', '9780132350884', 'BBC 3102 Software Project', 'Recommended by our lecturer for the final year project but the library has no copy.', 'pending'],
            [$members[3], $created['Research Methods for Business Students'], 'Research Methods for Business Students', 'Mark Saunders, Philip Lewis, Adrian Thornhill', '9781292208787', 'Research Methodology', 'Every final year student needs this for their proposal and there are no copies on the shelf.', 'approved'],
            [$members[4], null, 'Fundamentals of Corporate Finance', 'Richard Brealey, Stewart Myers', '9781260013962', 'BAF 2201', 'Main textbook for the course.', 'ordered'],
            [$members[1], $created['Laravel: Up & Running'], 'Laravel: Up & Running', 'Matt Stauffer', '9781098153267', 'BBC 2205 Web Development', 'Only one copy and it is always borrowed. Please buy more.', 'pending'],
        ];
        foreach ($suggestions as [$member, $book, $title, $author, $isbn, $course, $reason, $status]) {
            $s = BookSuggestion::create([
                'user_id' => $member->user_id, 'book_id' => $book?->id, 'title' => $title, 'author' => $author, 'isbn' => $isbn,
                'course' => $course, 'reason' => $reason, 'status' => $status,
                'response' => $status === 'approved' ? 'Approved for the next purchase order.' : ($status === 'ordered' ? 'Five copies have been ordered.' : null),
                'responded_by' => $status === 'pending' ? null : $admin->id,
            ]);
            $voters = $members->shuffle()->take(random_int(1, 5))->push($member)->unique('id');
            foreach ($voters as $v) {
                DB::table('suggestion_votes')->insert(['book_suggestion_id' => $s->id, 'user_id' => $v->user_id, 'created_at' => now()]);
            }
            $s->update(['votes_count' => $voters->count()]);
        }

        // Sample past papers.
        foreach ([['Financial Accounting I Examination', 'BAF 1101', 2024], ['Database Systems Examination', 'BBC 2101', 2024], ['Principles of Marketing Examination', 'BMK 1101', 2023]] as [$title, $course, $year]) {
            $path = 'resources/'.str($title)->slug().'-'.$year.'.pdf';
            Storage::disk('local')->put($path, $this->samplePdf("{$title} {$year}"));
            DigitalResource::create(['title' => $title, 'course' => $course, 'year' => $year, 'type' => 'past_paper', 'file_path' => $path, 'original_name' => basename($path), 'uploaded_by' => $librarian->id]);
        }
    }

    /** A minimal valid one-page PDF so downloads open correctly. */
    protected function samplePdf(string $text): string
    {
        $text = str_replace(['(', ')'], '', $text);
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }
}
