# SmartLib — MUBS Library Management System

A web-based Library Management System for **Makerere University Business School (MUBS) Library**, built with **Laravel 12** (PHP 8.2+) (Group 12 proposal).
It provides an online catalogue, QR code borrowing and returning, reservations with a waiting list, SMS and email reminders, overdue fines paid with Mobile Money, past papers, **public book recommendations** and reports.

## Features

| Module | What it does |
|---|---|
| **Catalogue (OPAC)** | Anyone (no login) can search by title, author, ISBN, subject or keyword, filter by category/availability and see copies, shelf location and the next due date. |
| **Self-registration** | Anyone (student or staff) can register at `/register`, gets a library number and a QR library card. |
| **Book recommendations** | Members recommend books the library doesn't have, or more copies of a book that is never available. **The list is public**; logged-in users support (vote for) recommendations they also need, duplicates are merged into votes, and librarians reply (Approved / Ordered / Now in library / Not approved) — the member gets an email. A "Recommend" button appears when a search finds nothing or a book has no copy on the shelf. |
| **Cataloguing** | Titles, authors, categories, copies, cover images, and printable QR labels for each copy. |
| **Membership** | Librarians register/edit/suspend members, set borrowing limits and expiry, and print QR library cards. |
| **Circulation** | Issue and return by scanning QR codes with a phone/laptop camera (html5-qrcode) or a USB scanner. Due dates by member type, borrowing limits, blocks for overdue books or unpaid fines. Members and staff can renew (unless reserved or the renewal limit is reached). |
| **Reservations** | Members reserve books that are all on loan, see their place in the queue; a returned copy is held for the next member, who gets an SMS. Uncollected holds expire and pass to the next person. |
| **Notifications** | SMS via Africa's Talking and email: reminder before the due date, overdue notice, reservation ready. Everything is logged. |
| **Fines & payments** | Fines calculated automatically (per day, with a cap). Members pay with **MTN Mobile Money, Airtel Money or card via Flutterwave** and get a receipt. Librarians can record cash or waive a fine with a reason. |
| **Digital resources** | Upload past exam papers and e-books; members search by course/year and download. |
| **Reports** | Dashboard with charts (Chart.js); loans, overdue, fines collected, most borrowed titles and member activity exported to Excel (CSV) or PDF (print). |
| **Administration** | Staff accounts (administrator / librarian), loan rules and fine rates, SMS/email log and audit log. |

Roles: **Guest** (search only) · **Member** · **Librarian** · **Administrator (head librarian)**.

## Requirements

- PHP 8.2+ with `pdo_sqlite` or `pdo_mysql`, `mbstring`, `fileinfo` (XAMPP/Laragon on Windows is fine)
- Composer
- MySQL 8 / MariaDB (or SQLite for quick testing)

No Node.js build step is needed — Bootstrap, icons, Chart.js and the QR scanner are bundled in `public/vendor`, so the system also works on a campus network without internet.

## Setup

```bash
git clone <repo-url> smartlib && cd smartlib
composer install
cp .env.example .env
php artisan key:generate
```

**Database.** For a quick start keep `DB_CONNECTION=sqlite` (Laravel creates `database/database.sqlite`).
For MySQL, create a database (e.g. `smartlib`) and set in `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartlib
DB_USERNAME=root
DB_PASSWORD=
```

Then create the tables, load demo data and run the app:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open http://localhost:8000.

### Demo accounts (password: `password`)

| Role | Email |
|---|---|
| Administrator | admin@mubs.ac.ug |
| Librarian | librarian@mubs.ac.ug |
| Student | student@mubs.ac.ug (library no. 2400720657) |
| Staff member | lecturer@mubs.ac.ug |

Demo copies have codes like `BK-001-01` — type them into the Issue/Return page or scan the QR labels from *Books & copies → Print QR labels*.

> The camera scanner needs HTTPS or `localhost`. To scan with a phone on the same Wi-Fi, serve the app over HTTPS (e.g. a hosted server) or use a USB scanner / type the code.

## SMS, payments and daily reminders

- **SMS (Africa's Talking):** set `AFRICASTALKING_USERNAME`, `AFRICASTALKING_API_KEY` (and optionally `AFRICASTALKING_FROM`). Use username `sandbox` with a sandbox key for testing. Without keys, messages are written to `storage/logs/laravel.log` and the SMS log page.
- **Email:** configure the `MAIL_*` settings. By default (`MAIL_MAILER=log`) emails go to the log file.
- **Payments (Flutterwave):** set `FLUTTERWAVE_PUBLIC_KEY` and `FLUTTERWAVE_SECRET_KEY` (test keys work). Without keys, a built-in **sandbox checkout** lets you approve or decline a payment for demonstrations.
- **Daily job:** `php artisan library:daily` sends due-date reminders and overdue notices, updates fines and expires uncollected reservations. It is scheduled for 08:00 every day; run `php artisan schedule:work` while developing, or on the server add a cron entry:
  `* * * * * cd /path/to/smartlib && php artisan schedule:run >> /dev/null 2>&1`

Loan periods, borrowing limits, fine per day, fine cap, the fine level that blocks borrowing, renewals and hold days are set by the administrator under **Loan rules & fines**.

## Tests

```bash
php artisan test
```

The feature tests cover registration and login, catalogue management, issuing/returning with limits and fines, renewals, the reservation queue and hold expiry, daily reminders, Mobile Money (sandbox) payments, waivers, recommendations and voting, role-based access, and a render check of every page.

## Project structure

- `app/Services/CirculationService.php` — borrowing rules: issue, return, renew, fines, reservation queue
- `app/Services/NotificationService.php` — SMS (Africa's Talking) and email
- `app/Services/PaymentService.php` — Flutterwave / sandbox payments
- `app/Support/LibrarySettings.php` — configurable loan rules with defaults
- `app/Console/Commands/RunDailyLibraryTasks.php` — scheduled reminders
- `app/Http/Controllers` — public, member (`Member/`) and staff (`Admin/`) controllers
- `resources/views` — Blade templates (Bootstrap 5)
- `database/migrations`, `database/seeders/DatabaseSeeder.php` — schema and demo data
