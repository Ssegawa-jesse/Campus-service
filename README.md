# Pearl360 — Uganda Parks 360° Virtual Tour & Smart Trip Planner

A web-based 360° virtual tour and smart trip-planning system for Uganda's national parks. It is the implementation for the Group 12 research proposal (MUBS, Bachelor of Business Computing). The case study is the **Uganda Wildlife Authority (UWA)**.

Built with **Laravel 12 (PHP 8.2+)**, **MySQL**, **Bootstrap 5**, **Pannellum** (360° viewer), **Leaflet + OpenStreetMap** (maps) and **Chart.js** (analytics). Online payments use **Flutterwave** (MTN MoMo, Airtel Money, cards). SMS goes through **Africa's Talking**.

## Features

| Module | What it does |
|---|---|
| Park information | Profiles of all **10 UWA national parks**: description, activities with indicative costs, best months, difficulty, how to get there, gallery (photos, MP4 videos, YouTube), reviews |
| 360° virtual tours | 3 scenes per park, linked by clickable hotspots, with info points, auto-rotate, full screen and phone **motion/VR mode**. Every scene view is logged for analytics |
| Interactive map | All parks on Leaflet with street, satellite and terrain layers, search, "fly to", popups with a tour link, and "my location" distances |
| Smart trip planner | Rule-based recommender that scores every park out of 100 (interests 40, fitness 20, budget 15, season 15, days 10) and explains each ranking |
| Compare parks | Side-by-side comparison of 2–4 parks |
| Bookings | Tourist sends an enquiry → operator sends a quote → tourist **pays online** → operator confirms → trip completed → tourist reviews |
| Online payments | MTN Mobile Money, Airtel Money, Visa/Mastercard via Flutterwave. Includes an offline **simulator** for demos |
| SMS + email | Alerts at every booking step for tourist, operator and admin. There is also an admin **SMS centre** for single messages and broadcasts |
| Analytics | Admin: views per park, top scenes, popular interests, bookings per park, status, 12-month trends, revenue, CSV export for Excel/SPSS. Operator: enquiries, revenue, package views |
| User management | Roles: **admin (UWA)**, **tourist**, **tour operator**. Operators must be verified by the admin before tourists can see them |

## Quick start (XAMPP / Laragon / WAMP)

1. Install **PHP 8.2+**, **Composer** and **MySQL**. XAMPP 8.2.12 works: enable `extension=gd`, `extension=zip`, `extension=intl` and `extension=fileinfo` in `C:\xampp\php\php.ini`, then restart Apache.
2. In phpMyAdmin, create an empty database called `pearl360`.
3. In the project folder, run:

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed    # creates tables, all 10 parks, 360° scenes, demo users & data
php artisan serve
```

4. Open http://localhost:8000.

### Demo accounts (password `Pearl360@2026`)

| Role | Email |
|---|---|
| Administrator (UWA) | `2400725284@mubs.ac.ug` |
| Tourist | `tourist@pearl360.test` |
| Tour operator | `operator@pearl360.test` (also `nile@…`, `peaks@…`) |

> **Change the admin password** after the first login (Account settings). Set `SEED_DEMO_DATA=false` in `.env` to skip the fake bookings, reviews and views.

To re-install from scratch: `php artisan migrate:fresh --seed`.

## Adding your own photos, videos and 360° images

The seeder draws **placeholder** landscapes (in `public/uploads/seed/`) so everything works on day one. Replace them from the admin panel:

| What | Where | Tips |
|---|---|---|
| Park cover photo | Admin → Parks → *Edit* → Cover photo | Landscape, ~1600×900, under 500 KB |
| Gallery photos (many at once) | Admin → Parks → **Photos & videos** | Tick "Use as cover photo" on any image |
| Videos | Same page: upload MP4 (≤100 MB) **or** paste a YouTube link | For big files, raise `upload_max_filesize` / `post_max_size` in `php.ini` |
| Feature video on the park page | Admin → Parks → *Edit* → Feature video | |
| 360° panoramas | Admin → Parks → **360° scenes** → Upload | Must be **equirectangular** (width = 2 × height). Use an Insta360 / Ricoh Theta, or a phone app such as Google Street View. 4096×2048 JPG is ideal |
| Hotspots | Open a scene → **click in the panorama** to capture pitch/yaw → choose "Link to scene" or "Information point" | |
| New park / wildlife reserve | Admin → Parks → Add park → **click the map** to place the pin | |
| Home page hero image, contacts, USD rate | Admin → Settings | |

All uploads are saved to `public/uploads/…`, so they work on shared hosting without `storage:link`.

## Going live: payments, SMS, email

All three work out of the box in **demo mode**: payments use the simulator, SMS are written to the SMS centre log, and emails go to `storage/logs/laravel.log`. To switch on the real services, edit `.env`:

**Mobile Money & cards (Flutterwave)**
1. Create a business account at https://flutterwave.com (Uganda) and copy your API keys.
2. Set:
   ```
   PAYMENT_DRIVER=flutterwave
   FLUTTERWAVE_PUBLIC_KEY=FLWPUBK-...
   FLUTTERWAVE_SECRET_KEY=FLWSECK-...
   FLUTTERWAVE_SECRET_HASH=any-long-random-string
   ```
3. In the Flutterwave dashboard, set the webhook URL to `https://your-domain/payments/webhook` and use the same secret hash.

**SMS (Africa's Talking)**
```
SMS_DRIVER=africastalking
AT_USERNAME=your_username      # "sandbox" while testing
AT_API_KEY=your_api_key
AT_SANDBOX=false               # true while testing on the AT simulator
AT_SENDER_ID=                  # optional, once approved
```

**Email (Gmail example)**
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=youraccount@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=youraccount@gmail.com
```

Admin alerts go to `ADMIN_EMAIL` / `ADMIN_PHONE`, which default to `2400725284@mubs.ac.ug` / `256741141433`. You can change them later in Admin → Settings.

After editing `.env` on a live server, run `php artisan config:clear`.

## Tests

```bash
php artisan test
```

13 tests (in `tests/Feature` and `tests/Unit`) cover every page for every role, access control, operator registration, the trip planner's rankings, and the full **enquiry → quote → Mobile Money payment → confirmation** flow including SMS alerts, plus Ugandan phone-number formatting and YouTube link handling.

## Project structure (the Laravel files we use)

```
app/
├── helpers.php                         media_url(), ugx(), usd_to_ugx(), setting(), youtube_embed()
├── Http/
│   ├── Middleware/EnsureRole.php       role-based access (admin / tourist / operator)
│   └── Controllers/
│       ├── Controller.php              shared upload helper (saves to public/uploads)
│       ├── HomeController.php          home, map, about, contact
│       ├── ParkController.php          park list / filters, park page, compare
│       ├── TourController.php          360° viewer + scene-view logging
│       ├── TripPlannerController.php   planner form & results
│       ├── PackageController.php       public safari packages & operator pages
│       ├── AuthController.php          register (tourist/operator), login, profile
│       ├── PaymentController.php       checkout, simulator, Flutterwave callback & webhook
│       ├── Tourist/                    Dashboard (favourites, plans), Booking, Review
│       ├── Operator/                   Dashboard, Profile, Package (CRUD), Booking (quote/confirm)
│       └── Admin/                      Dashboard (+CSV), Park, Activity, Media, Scene (+hotspots),
│                                       Operator (verify), User, Booking/Payments, Review, Sms, Setting
├── Mail/SystemNotification.php         one branded email template for all notifications
├── Models/                             User, Park, Activity, Scene, Hotspot, ParkMedia, Operator,
│                                       TourPackage, Booking, Payment, Review, TripPlan, ViewLog,
│                                       SmsLog, Setting, ContactMessage
└── Services/
    ├── RecommendationService.php       the knowledge-based recommender (scoring rules)
    ├── NotificationService.php         every email + SMS in one place
    ├── Payments/                       PaymentGateway interface, PaymentService,
    │                                   FlutterwaveGateway, SimulatedGateway
    ├── Sms/SmsService.php              Africa's Talking / log driver, phone normalisation
    └── PlaceholderImageGenerator.php   draws placeholder panoramas & photos (PHP GD)
bootstrap/app.php                       middleware alias, CSRF exception for the payment webhook
config/pearl360.php                     project settings (admin contacts, payments, SMS)
database/
├── migrations/2026_01_01_*             users(role), parks, activities, scenes+hotspots, park_media,
│                                       operators+packages, bookings+payments, reviews, favourites,
│                                       trip_plans, view_logs, sms_logs, settings, contact_messages
├── seeders/                            ParkSeeder (10 parks), UserSeeder, SettingSeeder, DemoDataSeeder
└── factories/UserFactory.php
resources/views/                        Blade templates: layouts/, partials/, parks/, tour/, planner/,
                                        packages/, auth/, payments/, tourist/, operator/, admin/, emails/
routes/web.php                          all routes, grouped by role
public/css/pearl360.css                 the Pearl360 theme (change colours in :root)
public/js/pearl360.js                   small shared helpers
public/vendor/                          Bootstrap, Bootstrap Icons, Leaflet, Pannellum, Chart.js
                                        (served locally, so the demo works without internet except map tiles)
tests/Feature/, tests/Unit/             PagesTest, BookingFlowTest, TripPlannerTest, HelpersTest
```

### Database entities (proposal Table 3.3, extended)

`users` · `parks` · `activities` · `scenes` · `hotspots` · `park_media` · `operators` · `operator_park` · `tour_packages` · `bookings` · `payments` · `reviews` · `favourites` · `trip_plans` · `view_logs` · `sms_logs` · `settings` · `contact_messages`

## Notes for the research report

- Park costs are **indicative** foreign non-resident USD rates (e.g. gorilla permit $800, Kibale chimp tracking $250). Verify them against the current UWA tariff before publishing.
- Section 1.6 of the proposal says the system "will not process online payments". This implementation **adds** online payments and SMS, so update the scope section to match.
- The recommender is knowledge-based, as the proposal justifies (Ricci et al., 2015), because the system starts without historical user data.

## Deployment (shared hosting / cPanel)

1. Upload the project and point the domain's document root to the `public/` folder.
2. Create a MySQL database and put its details in `.env`, with `APP_ENV=production` and `APP_DEBUG=false`.
3. Run `composer install --no-dev --optimize-autoloader`, `php artisan key:generate`, `php artisan migrate --seed --force` and `php artisan config:cache`.
4. Make `storage/`, `bootstrap/cache/` and `public/uploads/` writable.

## Troubleshooting

- **"Your PHP version does not satisfy…" / Symfony needs 8.4:** you are probably running `composer update` or an old download. Run `composer install` (not update). It uses `composer.lock`, which is pinned to PHP 8.2.12 through `config.platform.php`.
- **"Call to undefined function imagecreatetruecolor":** enable `extension=gd` in `php.ini`. The seeder uses GD to draw the placeholder images.
- **SQLSTATE connection refused:** start MySQL in the XAMPP control panel and check the `DB_*` values in `.env`.
