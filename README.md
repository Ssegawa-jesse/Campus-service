# Event Pass MUBS – Campus Events & Digital Tickets

An Android app (Kotlin + Jetpack Compose) that lets Makerere University Business School (MUBS)
students discover campus events, book tickets, and carry QR-code tickets on their phones.
Authorised organizers can create events, track bookings, and scan tickets at the entrance so
nobody gets in twice on the same ticket.

## Features (mapped to the project objectives)

| Objective from the project description | Where it is in the app |
|---|---|
| **User registration & login** | Students sign up with name, email, student number and password. Organizers sign up with an *organizer access code* so only authorised people get organizer tools. Passwords are stored as salted PBKDF2 hashes. The app remembers who is logged in. |
| **Browse campus events** | *Events* tab: upcoming events with date, venue, price and seats left. Includes search and category filters (Conference, Career Fair, Workshop, Seminar, Social, Associations, Sports). |
| **Event registration & booking** | Event details → **Register** (free) or **Book ticket** (paid). One booking per student per event. Capacity is enforced, so sold-out events can't be overbooked. |
| **Payments (simulated)** | Paid bookings stay *Awaiting payment* until a payment confirmation arrives. The prototype simulates MTN Mobile Money / Airtel Money: it detects the network from the number and confirms after a short delay. PIN `0000` simulates a declined payment. |
| **Digital ticket generation** | When a booking is confirmed, a unique ticket code (e.g. `EPM-7KQ2-X9HD-4MWT`) and a QR code are generated. The ticket is shown under *My Tickets*. |
| **Ticket verification & check-in** | Organizer *Check-in* tab: scan the QR code with the camera, or type the code. The result shows **VALID** (the ticket is then marked used), **ALREADY USED** (with the time it was scanned), wrong event, unpaid/cancelled, or invalid/fake. |
| **Organizer dashboard** | Totals for events, tickets, check-ins and revenue. Create and edit events, cancel an event, see the attendee list with payment and check-in status for each attendee. |

## Demo accounts

The app sets these up the first time it runs:

| Role | Email | Password |
|---|---|---|
| Student | `student@mubs.ac.ug` | `student123` |
| Organizer | `organizer@mubs.ac.ug` | `organizer123` |

Organizer access code for creating new organizer accounts: `MUBS-ORG-2026`

## Getting the APK on a phone

### Option A – download the APK built by GitHub
1. Push to GitHub. The **Build Android APK** workflow (`.github/workflows/android.yml`) runs the unit tests and builds the APK.
2. Open the repository on GitHub → **Actions** → the latest *Build Android APK* run → under **Artifacts**, download `EventPassMUBS-apk`. Unzip it to get `app-debug.apk`.
3. Copy the APK to the phone and open it. Allow *Install unknown apps* for your file manager or browser when Android asks.

### Option B – build it yourself with Android Studio
1. Install [Android Studio](https://developer.android.com/studio) (Ladybug or newer).
2. **File → Open** and choose this folder. Wait for Gradle sync to finish.
3. Plug in an Android phone with USB debugging on (or start an emulator) and press **Run ▶**.
   To get an APK file instead, use **Build → Build App Bundle(s) / APK(s) → Build APK(s)**. The file is saved to
   `app/build/outputs/apk/debug/app-debug.apk`.

Command line (requires the Android SDK, with `ANDROID_HOME` set):
```bash
./gradlew testDebugUnitTest   # unit tests
./gradlew assembleDebug       # builds app/build/outputs/apk/debug/app-debug.apk
```

Requirements: Android 8.0 (API 26) or newer. A camera is needed for QR scanning (codes can also be typed in).

## Suggested demonstration flow
1. Log in as the **student**, open *MUBS Career Fair 2026* and tap **Register (free)**. The QR ticket appears right away.
2. Open *East African Business Conference* → **Book ticket** → pay with `0772123456` and PIN `1234`. The ticket is issued after the payment is confirmed.
3. Log out and log in as the **organizer**. The dashboard shows the new bookings.
4. Go to **Check-in**, pick the event, then scan the student's QR code (from a second phone or a screenshot) or type the code. You get **VALID**.
5. Verify the same ticket again. You get **ALREADY USED**, which shows that duplicate entry is blocked.
6. Create a new event with **New event**. It appears for students straight away.

## Project structure

```
app/src/main/java/ug/ac/mubs/eventpass/
├── EventPassApp.kt / MainActivity.kt     App start-up and dependency container
├── domain/                               Pure Kotlin logic (password hashing, ticket codes, validation, formatting)
├── data/                                 Room database: entities, DAOs, repository, ticket verification, demo data
└── ui/
    ├── EventPassRoot.kt                  Session handling, navigation and bottom tabs per role
    ├── auth/                             Login & registration
    ├── student/                          Events list, event details, payment, my tickets, ticket QR
    ├── organizer/                        Dashboard, create/edit event, attendees, QR check-in
    ├── common/                           Shared composables (QR code, cards, buttons)
    └── theme/                            MUBS colours (Material 3)
app/src/test/                             JVM unit tests for domain logic and ticket verification rules
```

**Technologies:** Kotlin, Jetpack Compose (Material 3), Navigation Compose, ViewModel + StateFlow,
Room (SQLite), ZXing (QR generation) and ZXing Android Embedded (camera scanning).

## Prototype limitations & next steps
- **Data is stored on the device.** Students and organizers share one database only when they use the same phone.
  For real use across many phones, move the repository to a backend (for example Firebase Auth + Firestore, or a REST API).
  Only `EventPassRepository` needs to change.
- **Payments are simulated.** For real payments, integrate the MTN MoMo / Airtel Money collection APIs and confirm bookings
  from the payment provider's callback on the server.
- Possible additions: push notifications and reminders, event posters/images, refunds, exporting attendance to CSV.
