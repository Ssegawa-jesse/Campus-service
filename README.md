# Murchison Falls Smart Trip Planning Platform (Laravel)

**MUBS IT Project (BBC III).** *Design and Implementation of an AI-Assisted Smart Trip Planning and Interactive 360° Preview Platform for Murchison Falls National Park.*

- **Institution:** Makerere University Business School, Faculty of Computing & Informatics
- **Supervisor:** Dr. Kasule Abdullah
- **Team:** Nayiga Sharlot · Ssegawa Jesse · Akech Daniella · Mawanda Jabel · Niyomukunzi H Adeline
- **Scope:** Murchison Falls National Park (Uganda) only
- **Research contact / admin:** `2400725284@mubs.ac.ug` · `+256 741 141 433`

Stack: **Laravel 12 (PHP 8.2+)**, MySQL (or SQLite), Bootstrap 5, **Leaflet + OpenStreetMap** for the map, **Pannellum** for 360° viewing, Chart.js, the **Anthropic Claude API** (optional) for the assistant, Flutterwave for MTN/Airtel Mobile Money and cards (with an offline simulator), and Africa's Talking for SMS (with a log mode).

---

## The six modules (supervisor's reference architecture)

| # | Module | URL | What it does |
|---|---|---|---|
| 1 | **Smart Itinerary** *(primary target)* | `/itinerary` | Builds a personalised, timed, multi-day schedule. It scores every activity against the visitor's interests, fitness, season and budget, then places the activities into dawn/morning/afternoon/evening/night slots. It handles travel days, Paraa ferry crossings, overnight stays, tips, the packing list and the reasons for each choice. Plans can be saved, printed, **booked and paid**. |
| 2 | **Travel Budgeting** | `/budget` | UWA tiered pricing: **Foreign Non-Resident (USD)**, **Foreign Resident (USD)** and **East African Citizen (UGX)**. Covers park entry, activity permits, accommodation tiers, meals, transport (4x4 hire or self-drive fuel), vehicle entry, the ferry and a 10% contingency. |
| 3 | **Smart Navigation** | `/navigate` | Leaflet map of Kichumbanyobo, Bugungu, Tangi, Chobe and Wankwar gates, Paraa Jetty, Baker's Trail, Pakuba Airfield and the attractions. A route planner gives distance and travel time and adds the ferry when you cross the Nile. Also shows your GPS position. |
| 4 | **AI Travel Assistant** | `/assistant` | Answers questions on weather, seasons, packing, UWA rules, fees, gates and the ferry. It recommends itineraries for a given budget and number of days by running the real itinerary engine. Uses Claude when `ANTHROPIC_API_KEY` is set and an offline knowledge base otherwise. Every question is logged. |
| 5 | **Travel Updates** | `/updates` | Live-editable alerts: Paraa ferry schedule, road and gate status, weather advisories and UWA conservation notices. Alerts have a severity, can be pinned and have validity dates. Includes a 7-day Open-Meteo forecast for Paraa. |
| 6 | **Virtual Exploration** *(secondary)* | `/explore` | A 360° viewer for each activity. Hotspots show a title, description and audio note, plus an **"Add to Smart Itinerary"** button. Activity photos and videos appear below the viewer. |

### HP5 360° Media & Hotspot Studio (Admin → `/admin/studio`)
1. Upload an **equirectangular** JPG exported from the **HP5 360 VR camera** (2:1, e.g. 5760×2880; 4096×2048 loads fastest). Choose the activity it belongs to.
2. In the editor, **click anywhere in the panorama**. This captures the pitch and yaw. Then add a title, a description, a private field note, an **audio note** (mp3/m4a/wav) and the activity for the "Add to Smart Itinerary" button.
3. Edit or delete hotspots and scenes at any time. You can also hide a scene, replace its image, or set its starting view.
4. Upload standard photos, MP4 videos or YouTube links per activity under **Park profile & gallery → Photos & videos**.

## Three portals (role switching)

| Role | Login (password `Pearl360@2026`) | Portal |
|---|---|---|
| **Admin & MUBS Researcher** | `2400725284@mubs.ac.ug` | `/admin`: HP5 360° studio, activities & UWA prices, map places, travel updates, itineraries, bookings, payments, **SMS & email logs + test sender**, AI assistant logs, operators, users, settings |
| **Tour Operator** | `operator@pearl360.test` | `/operator`: Murchison safari packages (photo, **promo video upload or YouTube link**, available places and dates), incoming bookings, quotes and confirmations |
| **Tourist** | `tourist@pearl360.test` | `/my`: Smart Itineraries, bookings, **online payment** (MTN MoMo, Airtel Money, Visa/Mastercard), budget, assistant, 360° previews |

To switch role, use **Log out / switch role** in the user menu and log in with another account. Change the admin password after the first login.

**Booking flow:** Generate itinerary → Save → *Book & pay* (choose an operator) → pay with MoMo/Airtel/card (simulator) → **instant email + SMS** to the tourist, the operator and the admin contact (`2400725284@mubs.ac.ug`, `+256741141433`). Every message is stored and visible under **Admin → SMS & email logs**.

---

## Run it locally

### Windows + XAMPP (easiest)
1. Start **MySQL** in XAMPP.
2. Double-click **`setup.bat`**. It creates `.env`, the app key and the database `murchison_smart_trip`, then migrates, seeds and starts the site at http://localhost:8000.

### Any OS
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
To use SQLite instead of MySQL, set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite` in `.env`, then run `touch database/database.sqlite`. If something fails, run `php artisan pearl360:doctor --fix`.

### Optional: turn on generative AI answers
```env
ANTHROPIC_API_KEY=your-key
ANTHROPIC_MODEL=claude-opus-5-5
```
Without a key, the assistant still works from its built-in knowledge base. If the AI is unavailable or declines a question, the knowledge base answers instead.

### Run the tests
```bash
php artisan test
```
There are 40 tests, including `tests/Feature/SmartTripModulesTest.php`. It covers the itinerary engine, UWA tier maths, routes and ferry detection, the assistant, travel updates, hotspots, "add to itinerary", and the full save → book → MoMo payment → email/SMS flow.

---

## Deploying

### Option A – Vercel (PHP runtime)
The repo includes `vercel.json` and `api/index.php` for the community **vercel-php** runtime.

1. Push the repo to GitHub and import it at **vercel.com → New Project**. Framework preset: **Other**.
2. Under **Settings → Environment Variables**, add:
   - `APP_KEY` (from `php artisan key:generate --show`) and `APP_URL`
   - an external database (for example a hosted MySQL): `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - optionally `ANTHROPIC_API_KEY`
3. Deploy. Then run `php artisan migrate --seed` once **from your own computer** against the same database (put the production `DB_*` values in your local `.env` temporarily).

> Vercel functions are serverless, so the filesystem is read-only and temporary. Seeded images work, but **files you upload later (HP5 panoramas, audio, videos) are not kept**. For field research with uploads, use Option B, or move uploads to S3-compatible storage. Sessions use cookies and the cache uses memory on Vercel.

### Option B – Railway / Render / cPanel shared hosting (recommended for uploads)
- **Railway or Render:** create a PHP/Laravel service plus a MySQL database, set the same environment variables, use `php artisan migrate --force --seed && php artisan serve --host 0.0.0.0 --port $PORT` as the start command, and attach a persistent volume to `public/uploads`.
- **cPanel:** upload the project, point the domain's document root to `public/`, create a MySQL database, edit `.env`, then run `php artisan migrate --seed` (through the Terminal, or *Setup PHP App*).

### Real payments, SMS and email
- `PAYMENT_DRIVER=flutterwave` with `FLUTTERWAVE_PUBLIC_KEY`, `FLUTTERWAVE_SECRET_KEY` and `FLUTTERWAVE_SECRET_HASH`
- `SMS_DRIVER=africastalking` with `AT_USERNAME` and `AT_API_KEY`
- `MAIL_MAILER=smtp` plus your SMTP settings

The simulator and log drivers are the defaults, which suits demos and the project defence.

---

## Where things live

| Path | Purpose |
|---|---|
| `app/Services/Murchison/ItineraryEngine.php` | Smart Itinerary algorithm (score → place in day/slot grid → schedule → budget → explain) |
| `app/Services/Murchison/BudgetCalculator.php` | UWA three-tier budget |
| `app/Services/Murchison/RouteService.php` | Haversine distance, road factor, travel time, ferry rule |
| `app/Services/Murchison/TravelAssistant.php` | Claude + knowledge-base assistant, grounded in live database facts |
| `config/murchison.php` | Park entry fees, accommodation tiers, transport, origins, seasons, interests |
| `database/seeders/ParkSeeder.php` | Murchison Falls park, map places, the 7 activities with UWA prices, placeholder 360° scenes |
| `app/Http/Controllers/Smart/*` | Public module controllers |
| `app/Http/Controllers/Admin/{SceneController,TravelUpdateController,PlaceController,UwaActivityController,NotificationLogController}.php` | Researcher CMS |
| `docs/screenshots/` | Implementation screenshots for Chapter 4 of the report |
| `docs/MUBS_Proposal_Murchison_Smart_Trip_Planning.docx` | First draft of the project proposal in the MUBS format |
| `murchison-smart-tourism/` | Earlier Next.js prototype, kept for reference |

Fees are **indicative UWA planning figures**: confirm current tariffs with the Uganda Wildlife Authority and update them under **Admin → Activities & UWA prices**. Map coordinates are approximate and meant for orientation, not navigation.
