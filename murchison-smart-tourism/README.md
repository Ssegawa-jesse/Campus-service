# Murchison Falls Smart Tourism Platform

A web-based smart trip planning system with 360° virtual exploration for **Murchison Falls National Park**
(Uganda Wildlife Authority). MUBS Group 12, Bachelor of Business Computing.

Built with **Next.js 16** (React, TypeScript), so it deploys directly to **Vercel**. No database is needed:
the park data lives in `lib/data.ts`, saved itineraries are kept in the visitor's browser, and itineraries can be shared by link.

## Features

| Page | Feature |
|---|---|
| `/plan` | **Smart itinerary (main feature)** – day-by-day timed schedule from the visitor's dates, interests, fitness, budget level, pace, transport and group; explains every choice, lists what was left out and why, packing list, forecast per day, save / share / print |
| `/budget` | **Travel budgeting** – park entry, activities, accommodation, meals, transport, vehicle entry and ferry, contingency, in US$ and UGX |
| `/navigate` | **Smart navigation** – interactive map (Leaflet + OpenStreetMap) of attractions, gates and towns, approximate route from Kampala, Google Maps directions |
| `/explore` | **Virtual exploration** – built-in 360° viewer (Pannellum) with hotspots, plus H5P "Virtual Tour (360)" tours |
| `/assistant` | **AI travel assistant** – uses Claude when `ANTHROPIC_API_KEY` is set; otherwise answers from the park data |
| `/updates` | **Travel updates** – live 16-day weather at Paraa (Open-Meteo, no key needed), best months, travel notices |

## Run it on your computer

Install **Node.js 20 or newer** (https://nodejs.org), then in this folder:

```bash
npm install
npm run dev
```

Open http://localhost:3000.

## Deploy to Vercel

1. Push the repository to GitHub.
2. In Vercel choose **Add New → Project** and import the repository.
3. Set **Root Directory** to `murchison-smart-tourism` (the framework is detected as Next.js).
4. Optional: under **Environment Variables** add `ANTHROPIC_API_KEY` to switch the travel assistant to Claude.
5. Press **Deploy**.

## Updating the content

Everything the system shows comes from **`lib/data.ts`**:

- `ACTIVITIES` and `PARK_FEES` – activities, fees (per visitor category) and times. **Fees are planning estimates – update them from the current UWA tariff.**
- `ATTRACTIONS` – places shown on the map (approximate positions).
- `TIERS`, `ORIGINS`, `UGX_PER_USD` – accommodation prices, distances and exchange rate.
- `MONTHS`, `TRAVEL_NOTICES` – seasons and notices on the Travel updates page.

### Replacing the images

The images in `public/images/` are illustrations standing in for real photos. Replace them with your own
photos **using the same file names**:

| File | Used for |
|---|---|
| `public/images/hero.jpg` | Home page banner |
| `public/images/gallery/*.jpg` | Photo gallery |
| `public/images/scenes/top-of-falls.jpg`, `paraa-nile.jpg`, `north-bank-savannah.jpg` | 360° scenes (must be 360° photos in 2:1 equirectangular JPG, about 4096 × 2048) |

To add another 360° scene, put the image in `public/images/scenes/` and add it to `SCENES` in `lib/data.ts`
with its hotspots (`pitch` = up/down, `yaw` = left/right in degrees).

### Adding an H5P tour

1. Take 360° photos with a 360° camera (e.g. Insta360 or Ricoh Theta) and export them as 360° JPG.
2. In H5P (for example the free **Lumi** desktop app), create a **Virtual Tour (360)**, add the photos as scenes and place hotspots.
3. Export it as a single HTML file into `public/h5p/` (e.g. `public/h5p/murchison-tour.html`), or copy the embed link from h5p.com.
4. Add it to `H5P_TOURS` in `lib/data.ts`:
   ```ts
   { title: 'Murchison Falls 360° tour', src: '/h5p/murchison-tour.html', description: 'Built with H5P from our own photos.' }
   ```

## Project structure

```
app/                 pages (one folder per feature) and app/api/assistant (assistant API)
components/          interactive parts: Planner, BudgetCalculator, ParkMap, PanoViewer, Chat, Weather, Nav
lib/data.ts          all park content
lib/planner.ts       smart itinerary engine
lib/budget.ts        cost rules shared by the planner and the budget page
lib/assistant.ts     assistant knowledge and built-in answers
lib/weather.ts       Open-Meteo forecast
public/              images, H5P tours and the Pannellum viewer
```

## How the smart itinerary works

1. Every activity is scored: +10 for each interest it matches, +2 × its importance for a first visit, +100 if the visitor marked it as a must-do.
   Activities above the visitor's fitness level, or needing a special interest (chimpanzees, balloon, fishing, rhinos, hiking) that was not chosen, are left out and listed with the reason.
2. The best-scoring activities are placed into time slots (dawn, morning, afternoon, evening) on the days of the trip, following park logic:
   game drives at dawn on the northern bank, launch trips in the afternoon, the Top of the Falls on the arrival afternoon,
   chimpanzee tracking on the way in or out, and at most two (relaxed) or three (full pace) activities a day.
3. Travel, overnight stays, tips and the forecast for each date are added, then the budget is worked out with the same rules as the budget page.
