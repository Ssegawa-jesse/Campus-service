# MUBS Library — Online Book Reservations (Laravel 12)

Answer to **Question Two (20 marks)**: the MUBS Library reservation form from the
`mubs-library-reservation-frontend` templates, wired to a database with Laravel 12 so that a
student's reservation is saved instead of vanishing on submit.

## Where each part of the question is answered

| Part | Marks | What was done | Files |
| --- | --- | --- | --- |
| **a) Views + Blade layout** | 5 | Main layout with a header, footer and `@yield('content')` section. Header and footer are `@include` partials. Pages `@extends` the layout. The form collects a reservation, and the list shows saved reservations with `@forelse`. | `resources/views/layouts/app.blade.php`<br>`resources/views/layouts/partials/header.blade.php`<br>`resources/views/layouts/partials/footer.blade.php`<br>`resources/views/welcome.blade.php`<br>`resources/views/reservations/index.blade.php`<br>`resources/views/reservations/create.blade.php`<br>`resources/views/reservations/edit.blade.php`<br>`resources/views/reservations/partials/form.blade.php` |
| **b) Routes** | 2 | Named home route plus a resource route for the reservation pages | `routes/web.php` |
| **c) Controller** | 4 | `index`, `create`, `store` (validate + save), `edit`, `update`, `destroy`, with flash messages | `app/Http/Controllers/ReservationController.php`<br>`app/Http/Controllers/HomeController.php` |
| **d) Model + Migration** | 5 | `Reservation` model with `$fillable` and a date cast. The migration creates the `reservations` table. | `app/Models/Reservation.php`<br>`database/migrations/2026_11_23_000000_create_reservations_table.php` |
| **e) Database** | 4 | `.env` points at the MySQL database `mubs_library` (XAMPP). Sample data comes from the seeder. | `.env.example`, `database/seeders/DatabaseSeeder.php` |

The template's CSS and the MUBS crest are in `public/assets/` and are loaded with `asset()`.

## Routes

| Method | URL | Name | Action |
| --- | --- | --- | --- |
| GET | `/` | `home` | Library home page |
| GET | `/reservations` | `reservations.index` | List reservations |
| GET | `/reservations/create` | `reservations.create` | Reservation form |
| POST | `/reservations` | `reservations.store` | Save a reservation |
| GET | `/reservations/{id}/edit` | `reservations.edit` | Edit form |
| PUT | `/reservations/{id}` | `reservations.update` | Save changes |
| DELETE | `/reservations/{id}` | `reservations.destroy` | Delete |

## Form fields = database columns

| Form `name` | Column type | Validation |
| --- | --- | --- |
| `student_name` | string | required, max 255 |
| `registration_number` | string(50) | required, max 50 |
| `programme` | string | required, one of the listed programmes |
| `book_title` | string | required, max 255 |
| `pickup_date` | date | required. A new reservation cannot be for a past date. |

## Running it (Windows + XAMPP)

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. In phpMyAdmin, create a database called `mubs_library`.
3. In this folder, run:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

4. Open http://127.0.0.1:8000.

To use SQLite instead of MySQL, set `DB_CONNECTION=sqlite` in `.env` and delete the other `DB_` lines.

## How it was built (artisan commands)

```bash
composer create-project laravel/laravel:^12.0 mubs-library-reservation
php artisan make:controller HomeController
php artisan make:model Reservation -mcrf      # model + migration + resource controller + factory
php artisan migrate --seed
```

## Tests

```bash
php artisan test
```

The feature tests in `tests/Feature/ReservationTest.php` check that the pages load, that a reservation saves and shows in the list, that invalid input is rejected, and that edit and delete work.
