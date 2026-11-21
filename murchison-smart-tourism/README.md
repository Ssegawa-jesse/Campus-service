# Murchison Falls Smart Tourism Platform

A web-based **smart trip planning system** with 360° virtual exploration for **Murchison Falls National Park**
(Uganda Wildlife Authority). MUBS Group 12, Bachelor of Business Computing.

Built with **Next.js 16** (React, TypeScript) so it deploys directly to **Vercel**.

## Features

**For everyone (no login needed)**

| Page | Feature |
|---|---|
| `/plan` | **Smart itinerary (main feature)** – timed day-by-day plan from dates, interests, fitness, budget level, pace and transport; reasons for every choice, packing list, forecast per day, cost estimate; save, share, print, show on map, **book this itinerary**, and **view in 360°** for activities that have a scene |
| `/budget` | Travel budgeting in US$ and UGX |
| `/navigate` | Map of attractions and the route from Kampala, with directions |
| `/explore` | 360° scenes with hotspots for the activities (uploaded by the admin) and H5P tours |
| `/operators` | Approved tour operators and their packages |
| `/assistant` | AI travel assistant (Claude when `ANTHROPIC_API_KEY` is set, built-in answers otherwise) |
| `/updates` | Live weather at Paraa, best months, travel notices |

**Accounts**

| Role | Can do |
|---|---|
| **Tourist** | Register, book an operator (from a package or from their smart itinerary), receive the quote by SMS, pay by **MTN Mobile Money, Airtel Money or card**, follow the booking |
| **Tour operator** | Register (waits for admin approval), manage company profile and packages, quote, confirm and complete bookings, see revenue |
| **Administrator** | Approve / reject operators, manage users and **add more administrators**, see all bookings and payments, SMS log and test SMS, login activity, and **upload 360° photos for each activity and place hotspots by clicking in the photo** |

SMS is sent at each step: registration, new operator (to admins), approval, enquiry, quote, payment, confirmation.

## Run it on your computer

1. Install **Node.js 20 or newer** from https://nodejs.org.
2. In this folder, copy `.env.example` to a new file named **`.env.local`** and fill in at least:
   ```
   ADMIN_EMAIL=your email
   ADMIN_PASSWORD=a password of 8+ characters
   ADMIN_NAME=your name
   ADMIN_PHONE=your phone number
   ```
   (Without these, the first admin is `admin@murchison.local` with password `Murchison@2026` – change it after logging in.)
3. Open a terminal in this folder and run:
   ```
   npm install
   npm run dev
   ```
4. Open http://localhost:3000 and log in with the admin email and password.

On your computer the database is the file `murchison.db` and uploaded photos go to the `uploads` folder – both are created automatically.
Payments use a **simulator** and SMS are only **logged** until you add the Flutterwave and Africa's Talking keys.

Run the automated tests with `npm test`.

## Deploy to Vercel

1. In Vercel: **Add New → Project**, import the GitHub repository, set **Root Directory** to `murchison-smart-tourism`.
2. **Database** – create a free database at https://turso.tech (Create database → copy the URL and create a token).
3. **Photo storage** – in the Vercel project: **Storage → Create → Blob** and connect it (this adds `BLOB_READ_WRITE_TOKEN`).
4. **Environment Variables** (Settings → Environment Variables):
   - `SESSION_SECRET` – any long random text (required)
   - `TURSO_DATABASE_URL`, `TURSO_AUTH_TOKEN` – from Turso
   - `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`, `ADMIN_PHONE` – the first administrator
   - Optional: `FLW_SECRET_KEY` (Flutterwave live payments), `AT_USERNAME` + `AT_API_KEY` (Africa's Talking SMS), `ANTHROPIC_API_KEY` (AI assistant), `APP_URL` (your site address)
5. **Deploy**. The tables and the first admin are created on the first visit.

## Adding your 360° photos and hotspots

1. Log in as admin → **360° scenes & hotspots**.
2. Choose a 360° photo (2:1 JPG, about 4096 × 2048), give it a title and choose the **activity** it shows (launch trip, game drive, chimpanzee tracking…), then **Save and add hotspots**.
3. Drag to look around, **click** where a hotspot should go, choose *Information* or *Go to another scene*, type the text and **Add hotspot**.
4. Press **Publish on the website**. Published scenes replace the sample scenes on `/explore`, and the smart itinerary shows a **View in 360°** link for that activity.

H5P tours can still be added as described in `lib/data.ts` (`H5P_TOURS`): export the tour from Lumi as one HTML file into `public/h5p/`.

## Updating park information

Activities, fees (planning estimates – update from the current UWA tariff), places, seasons and notices are in **`lib/data.ts`**.
The pictures in `public/images/` are stand-ins: replace them with your own photos using the same file names.

## Project structure

```
app/                     pages: public pages, /tourist, /operator, /admin, /book, /pay, /login, /register, /account
app/actions/             server actions: auth, bookings, admin
app/api/                 assistant, payments callback, uploads, scenes
components/              Planner, BudgetCalculator, ParkMap, PanoViewer, Chat, Weather, dashboards and forms
lib/planner.ts           smart itinerary engine          lib/budget.ts     cost rules
lib/db.ts                database and tables             lib/auth.ts       login sessions and roles
lib/payments.ts          Flutterwave + simulator         lib/sms.ts        Africa's Talking + SMS log
lib/storage.ts           photo uploads                   lib/data.ts       park content
tests/                   automated tests (npm test)
```
