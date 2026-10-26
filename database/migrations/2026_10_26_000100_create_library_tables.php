<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('member_no')->unique();
            $table->string('type', 20)->default('student'); // student, staff
            $table->string('programme')->nullable();
            $table->unsignedTinyInteger('max_loans')->nullable(); // null = use setting for member type
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('isbn', 20)->nullable()->index();
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('edition')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->timestamps();
        });

        Schema::create('author_book', function (Blueprint $table) {
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->primary(['author_id', 'book_id']);
        });

        Schema::create('copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique(); // encoded in the QR label
            $table->string('shelf_location')->nullable();
            $table->string('condition', 20)->default('good'); // new, good, fair, poor
            $table->string('status', 20)->default('available'); // available, on_loan, on_hold, lost, damaged
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('copy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('issued_at');
            $table->dateTime('due_at');
            $table->dateTime('returned_at')->nullable();
            $table->unsignedTinyInteger('renewals')->default(0);
            $table->dateTime('reminder_sent_at')->nullable();
            $table->dateTime('overdue_notice_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('copy_id')->nullable()->constrained()->nullOnDelete(); // copy held for the member
            $table->string('status', 20)->default('waiting'); // waiting, ready, fulfilled, cancelled, expired
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount'); // UGX
            $table->string('status', 20)->default('unpaid'); // unpaid, paid, waived
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('method', 20); // mtn, airtel, card, cash
            $table->string('phone', 20)->nullable();
            $table->string('reference')->unique();
            $table->string('gateway_ref')->nullable();
            $table->string('status', 20)->default('pending'); // pending, successful, failed
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('digital_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('course')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('type', 20)->default('past_paper'); // past_paper, ebook, journal, other
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('book_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete(); // set when the title is in the catalogue but unavailable
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('course')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, ordered, added, rejected
            $table->text('response')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();
        });

        Schema::create('suggestion_votes', function (Blueprint $table) {
            $table->foreignId('book_suggestion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['book_suggestion_id', 'user_id']);
        });

        Schema::create('library_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 10); // sms, email
            $table->text('message');
            $table->string('status', 20)->default('sent'); // sent, failed, logged
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach ([
            'audit_logs', 'settings', 'library_notifications', 'suggestion_votes', 'book_suggestions',
            'digital_resources', 'payments', 'fines', 'reservations', 'loans', 'copies', 'author_book',
            'books', 'authors', 'categories', 'members',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
