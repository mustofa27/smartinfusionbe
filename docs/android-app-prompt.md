# Android App for Smart Infus — Prompt for AI Code Generation

## Project Overview

Build an Android application in **Kotlin** that serves as the nurse's mobile companion for the **Smart Infus** infusion pump monitoring system. The app connects to the backend Laravel API to allow nurses to scan device QR codes, monitor active infusion sessions, start/stop sessions, and receive real-time alerts via MQTT + FCM push notifications.

## Core Technology Stack

- **Language:** Kotlin
- **Minimum SDK:** 26 (Android 8.0 Oreo)
- **Architecture:** MVVM with Repository pattern
- **Networking:** Retrofit 2 + OkHttp + Gson/Moshi
- **Auth:** Laravel Sanctum token-based (Bearer token stored in EncryptedSharedPreferences)
- **Real-time (optional):** MQTT client (Eclipse Paho) for live infusion reading updates
- **Push:** Firebase Cloud Messaging (FCM)
- **QR scanning:** ML Kit Barcode Scanning or CameraX + ZXing
- **DI:** Hilt or Koin
- **Navigation:** Jetpack Navigation Component

## API Base URL

The app must be configurable — allow the user/IT admin to enter the server URL on first launch (stored in DataStore/SharedPreferences).

```
https://smartinfusion.icminovasi.my.id/api/v1
```

## Authentication

### Login Screen
- Fields: Organization Code, Email, Password, Device Name (optional, defaults to phone model)
- POST `/api/v1/auth/login`
- On success, store the Bearer token securely (EncryptedSharedPreferences)
- Response returns: `token`, `user.id`, `user.name`, `user.email`, `user.role`, `user.organization_id`

```
POST /api/v1/auth/login
{
  "organization_code": "RS001",
  "email": "nurse@example.com",
  "password": "secret",
  "device_name": "Samsung Galaxy S24"
}
→ 200
{
  "token": "1|abc123...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Nurse A",
    "email": "nurse@example.com",
    "role": "nurse",
    "organization_id": 1
  }
}
```

- All subsequent requests send header: `Authorization: Bearer {token}`
- Logout: POST `/api/v1/auth/logout` (deletes current token)

### Token expiry / 401 handling
- If any API call returns 401, clear stored token and redirect to login screen.

## Core Features & Screens

### 1. Dashboard — Active Sessions List
- GET `/api/v1/nurse/infusion-sessions/active`
- Shows list of active infusion sessions the nurse is subscribed to (via device subscriptions):
  - Device serial number
  - Fluid name
  - Patient name
  - Started at timestamp
  - Last remaining volume (ml)
  - Last flow rate (ml/hour)
  - Last reading timestamp
- Pull-to-refresh to reload.
- Each row taps to navigate to Session Detail.

```
GET /api/v1/nurse/infusion-sessions/active
→ 200
{
  "data": [
    {
      "id": 1,
      "device_id": 5,
      "patient_id": 3,
      "fluid_name": "NaCl 0.9%",
      "started_at": "2026-07-14T10:30:00Z",
      "last_remaining_ml": 250.00,
      "last_flow_ml_per_hour": 120.50,
      "last_reading_at": "2026-07-14T12:00:00Z"
    }
  ]
}
```

### 2. Scan QR Code (Primary Entry Point)
- Camera-based QR code scanner.
- The QR code contains: `smartinfus://device/{serial_number}`
- Extract `serial_number` from the scanned URL.
- POST to `/api/v1/nurse/monitor/by-device-code` with `{"device_code": "{serial_number}"}`.
- Possible responses:

**Case A (200): No active session** → show "Start Session" form
```
→ 200
{
  "session_required": true,
  "device": {
    "id": 5,
    "serial_number": "INF-001",
    "status": "online"
  },
  "required_fields": [
    "patient_id", "bed_id", "fluid_name", "bag_volume_ml",
    "bag_empty_weight_grams", "initial_weight_grams", "started_at"
  ]
}
```

**Case B (200): Active session exists** → navigate to Session Detail
```
→ 200
{
  "session_required": false,
  "device": { "id": 5, "serial_number": "INF-001", "status": "online" },
  "session": {
    "id": 1,
    "patient_id": 3,
    "bed_id": 10,
    "fluid_name": "NaCl 0.9%",
    "started_at": "2026-07-14T10:30:00Z",
    "last_remaining_ml": 250.00,
    "last_flow_ml_per_hour": 120.50,
    "last_reading_at": "2026-07-14T12:00:00Z"
  }
}
```

**Case C (403): User is not a nurse (role is admin/super-admin or other)** → show "Access Denied" error
```
→ 403
{
  "message": "Nurse role required."
}
```
What the app should do:
- Show a dialog or full-screen error: **"Access Denied — Only nurses can monitor devices. You are logged in as {role}."**
- Provide a **"Try Again"** button that reopens the QR scanner.
- Do NOT redirect to login (the user is authenticated, just lacks the right role).

**Case D (404): Device serial number not found in this organization** → show "Device Not Found" error
```
→ 404
{
  "message": "Device not found for this organization."
}
```
What the app should do:
- Show a dialog: **"Device not found. The serial number scanned does not exist in your organization."**
- Provide a **"Try Again"** button that reopens the QR scanner.
- Optionally include a **"Manual Entry"** fallback so the nurse can type the serial number again in case the QR code was damaged.

- **Important:** This endpoint automatically subscribes the nurse to the device (creates `NurseDeviceSubscription`).

### 3. Start Infusion Session (Form)
- Fields required (based on `required_fields`):
  - **Patient** — dropdown/autocomplete selector populated from `GET /api/v1/nurse/patients` (scoped to the nurse's organization, returns active patients only).
    ```
    GET /api/v1/nurse/patients
    → 200
    {
      "data": [
        { "id": 1, "medical_record_no": "MRN-001", "full_name": "John Doe" },
        { "id": 2, "medical_record_no": "MRN-002", "full_name": "Jane Smith" }
      ]
    }
    ```
  - **Bed** — dropdown/autocomplete selector populated from `GET /api/v1/nurse/beds` (scoped to the nurse's organization, returns active beds only, with ward/room/bed labels).
    ```
    GET /api/v1/nurse/beds
    → 200
    {
      "data": [
        { "id": 1, "bed_number": "01", "room_number": "101", "ward_name": "Ward A" },
        { "id": 2, "bed_number": "02", "room_number": "101", "ward_name": "Ward A" }
      ]
    }
    ```
  - **Fluid name** — text input
  - **Bag volume (ml)** — numeric input
  - **Bag empty weight (grams)** — numeric input
  - **Initial weight (grams)** — numeric input
  - **Started at** — date/time picker (optional, defaults to now)
  - **Notes** — text area (optional)
- POST `/api/v1/nurse/infusion-sessions/start`

```
POST /api/v1/nurse/infusion-sessions/start
{
  "device_code": "INF-001",
  "patient_id": 3,
  "bed_id": 10,
  "fluid_name": "NaCl 0.9%",
  "bag_volume_ml": 500,
  "bag_empty_weight_grams": 50,
  "initial_weight_grams": 550,
  "fluid_density_g_per_ml": 1.0,
  "started_at": "2026-07-14T10:30:00Z",
  "notes": "Started after shift handover"
}
→ 201
{
  "message": "Infusion session started.",
  "session": {
    "id": 1,
    "device_id": 5,
    "patient_id": 3,
    "status": "active",
    "started_at": "2026-07-14T10:30:00Z"
  }
}
```

### 4. Session Detail Screen
- Display:
  - Device serial number & status
  - Fluid name
  - Patient name / MRN (medical record number)
  - Bed location (Ward / Room / Bed)
  - Started at
  - **Live** remaining volume (ml)
  - **Live** flow rate (ml/hour)
  - **Live** last reading timestamp
  - Elapsed time counter

- **Real-time updates (via polling or MQTT):**
  - The device publishes sensor data via MQTT which gets stored as `InfusionReading` records. The `InfusionSession` has `last_remaining_ml`, `last_flow_ml_per_hour`, and `last_reading_at` fields that update on each reading.
  - **Option A (Polling):** Refresh the active sessions list every 5-10 seconds.
  - **Option B (MQTT):** Connect to the same MQTT broker and subscribe to the device's topic for real-time push. (Only if the Android app needs live sub-second updates; polling is simpler.)

- **Action buttons** (bottom of screen):
  - **Pause** — POST `/api/v1/nurse/infusion-sessions/{id}/pause`
    ```
    { "notes": "Paused for patient check" }
    ```
  - **Complete** — POST `/api/v1/nurse/infusion-sessions/{id}/complete`
    ```
    { "ended_at": "2026-07-14T14:00:00Z", "notes": "Bag finished" }
    ```
  - **Interrupt** — POST `/api/v1/nurse/infusion-sessions/{id}/interrupt`
    ```
    { "ended_at": "...", "notes": "Patient transferred" }
    ```

- Each action returns:
  ```
  {
    "message": "Infusion session paused.",
    "data": { "id": 1, "status": "paused" }
  }
  ```

### 5. Alerts
- GET `/api/v1/nurse/alerts?status=open` (status filter: open, acknowledged, resolved — optional)
- List of alerts with:
  - Alert type (e.g. "occlusion", "air_in_line", "low_battery", "flow_error", "near_empty", "empty", "disconnect")
  - Severity (e.g. "critical", "warning", "info")
  - Message text
  - Device / patient / session info
  - Triggered at timestamp
  - Status (open → acknowledged → resolved)
- **Acknowledge** action: POST `/api/v1/nurse/alerts/{id}/acknowledge`
  ```
  → 200
  { "message": "Alert acknowledged.", "data": { "id": 1, "status": "acknowledged", "acknowledged_at": "..." } }
  ```

### 6. Push Notifications (FCM)
- On login/startup, register the device's FCM token:
  ```
  POST /api/v1/nurse/fcm-tokens
  {
    "fcm_token": "firebase-token-here",
    "app_version": "1.0.0",
    "device_os": "Android 14",
    "device_model": "Samsung Galaxy S24"
  }
  ```
- The backend sends FCM push notifications when alerts are triggered for devices the nurse is subscribed to.
- When the user taps a push notification, deep-link to the Alerts screen or the relevant Session Detail.

## Data Models (reference for Android entities)

### Device
| Field | Type | Notes |
|-------|------|-------|
| id | Int | |
| serial_number | String | QR code identifier |
| mqtt_topic | String | IoT topic |
| model | String? | |
| firmware_version | String? | |
| status | String | online, offline, maintenance, retired |
| last_seen_at | DateTime? | |
| organization_id | Int | |

### InfusionSession
| Field | Type | Notes |
|-------|------|-------|
| id | Int | |
| device_id | Int | |
| patient_id | Int | |
| bed_id | Int | |
| fluid_name | String | |
| bag_volume_ml | Decimal | |
| bag_empty_weight_grams | Decimal | |
| initial_weight_grams | Decimal | |
| started_at | DateTime | |
| ended_at | DateTime? | |
| status | String | active, paused, completed, interrupted |
| last_remaining_ml | Decimal? | Live-updated |
| last_flow_ml_per_hour | Decimal? | Live-updated |
| last_reading_at | DateTime? | Live-updated |

### Alert
| Field | Type | Notes |
|-------|------|-------|
| id | Int | |
| device_id | Int | |
| patient_id | Int? | |
| infusion_session_id | Int? | |
| alert_type | String | occlusion, air_in_line, low_battery, flow_error, near_empty, empty, disconnect |
| severity | String | critical, warning, info |
| message | String | |
| triggered_at | DateTime | |
| status | String | open, acknowledged, resolved |
| acknowledged_at | DateTime? | |

## Recommended Screens / Navigation Graph

```
NavGraph:
  LoginScreen
  Dashboard (Active Sessions List) — default after login
    ├── SessionDetailScreen — tap session row
    │     └── (Pause / Complete / Interrupt actions)
    ├── QRScannerScreen — FAB or top bar icon
    │     ├── (403 denial → show AccessDeniedDialog → reopen scanner)
    │     ├── (404 denial → show DeviceNotFoundDialog → reopen scanner or manual entry)
    │     ├── StartSessionScreen — if 200 + session_required=true
    │     │     └── (success → SessionDetailScreen)
    │     └── SessionDetailScreen — if 200 + session_required=false
    └── AlertsScreen — bottom nav or top bar icon
          └── AlertDetail (optional)
```

## MQTT Integration (Optional but Recommended)
- If real-time updates are desired without polling:
  - Add Eclipse Paho MQTT client dependency.
  - Connect to the MQTT broker (configurable URL/port in settings, same as the backend's MQTT broker).
  - Subscribe to topics: `{device_mqtt_topic}` for each device the nurse is monitoring.
  - Parse incoming JSON payloads containing `remaining_ml`, `flow_ml_per_hour`, `weight_grams`, `timestamp` and update the UI.
  - The topic structure from the backend matches what is stored in `devices.mqtt_topic` column.

## Error Handling & Edge Cases

- **Network errors:** Show a Snackbar/toast with retry option. Implement exponential backoff for retries.
- **401 Unauthorized:** Clear session, redirect to login.
- **403 Forbidden — "Nurse role required":** Show "Access Denied — Only nurses can monitor devices. You are logged in as {role}." Do NOT log out. Let them retry scanning or switch accounts.
- **403 Forbidden — "Admin role required":** This occurs if a nurse calls any `GET /api/v1/admin/*` endpoint (patients, beds, etc.). These endpoints are admin-only. The Start Session form uses the nurse-scoped endpoints `GET /api/v1/nurse/patients` and `GET /api/v1/nurse/beds` instead, which return data for the nurse's organization.
- **404 Not Found:** Show "Device not found for this organization" dialog. The nurse can tap "Try Again" to re-scan or enter the serial manually.
- **422 Validation Error:** Parse the `message` and `errors` fields, display inline field errors in forms.
- **409 Conflict:** e.g. "Device already has an active infusion session" — show error and optionally navigate to the existing session if device ID is known.
- **Empty states:** Show helpful messages when no active sessions or no alerts exist.
- **QR code scan fails:** Allow manual entry of device serial number as fallback.
- **Token persistence:** Use EncryptedSharedPreferences. On app startup, check for existing valid token. If token exists, go to Dashboard; otherwise show Login.

## Project Structure (Recommended)

```
com.smartinfus.app/
├── data/
│   ├── api/
│   │   ├── ApiService.kt          (Retrofit interface)
│   │   ├── AuthInterceptor.kt      (attach Bearer token)
│   │   └── RetrofitClient.kt
│   ├── model/
│   │   ├── api/                    (DTOs matching JSON responses)
│   │   │   ├── LoginRequest.kt
│   │   │   ├── LoginResponse.kt
│   │   │   ├── SessionResponse.kt
│   │   │   ├── AlertResponse.kt
│   │   │   └── ...
│   │   └── domain/                 (domain entities)
│   │       ├── Device.kt
│   │       ├── InfusionSession.kt
│   │       ├── Alert.kt
│   │       └── User.kt
│   ├── repository/
│   │   ├── AuthRepository.kt
│   │   ├── SessionRepository.kt
│   │   └── AlertRepository.kt
│   ├── local/
│   │   ├── TokenManager.kt
│   │   └── SettingsDataStore.kt
│   └── mqtt/
│       └── MqttManager.kt
├── domain/
│   └── usecase/                    (optional, depends on complexity)
├── ui/
│   ├── login/
│   ├── dashboard/
│   ├── sessiondetail/
│   ├── scanner/
│   ├── startsession/
│   └── alerts/
├── di/
│   └── AppModule.kt
├── notification/
│   └── SmartInfusFirebaseService.kt
└── SmartInfusApp.kt
```

## Deliverables / What to Build

1. **Complete functional Android app** with all screens listed above.
2. **Working authentication flow** (login, token persistence, auto-login, logout).
3. **QR code scanning** to look up a device and either show the active session or prompt to start one — **including proper handling of 403 (role denial) and 404 (device not found)**.
4. **Session management** (view active sessions, start / pause / complete / interrupt).
5. **Alert management** (view open alerts, acknowledge).
6. **FCM push notification registration** so the backend can push alerts.
7. **Real-time updates** via polling (or MQTT if desired).
8. **Proper error handling** for network failures, validation errors, conflicts, and auth expiry.

## Dependencies (build.gradle.kts)

```kotlin
// Core
implementation("androidx.core:core-ktx:1.13.1")
implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.8.4")
implementation("androidx.navigation:navigation-fragment-ktx:2.7.7")

// Retrofit
implementation("com.squareup.retrofit2:retrofit:2.11.0")
implementation("com.squareup.retrofit2:converter-gson:2.11.0")
implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

// Security
implementation("androidx.security:security-crypto:1.1.0-alpha06")

// Camera / QR
implementation("com.google.mlkit:barcode-scanning:17.3.0")

// Firebase
implementation(platform("com.google.firebase:firebase-bom:33.1.2"))
implementation("com.google.firebase:firebase-messaging-ktx")

// MQTT (optional)
implementation("org.eclipse.paho:org.eclipse.paho.client.mqttv3:1.2.5")
implementation("org.eclipse.paho:org.eclipse.paho.android.service:1.1.1")

// DI
implementation("com.google.dagger:hilt-android:2.51.1")
kapt("com.google.dagger:hilt-compiler:2.51.1")

// DataStore
implementation("androidx.datastore:datastore-preferences:1.1.1")
```

## Important Notes

- All API responses wrap data in a `data` key for collection endpoints, but login returns top-level fields (`token`, `user`, etc.).
- Errors return JSON with a `message` field and sometimes a nested `errors` object for validation failures (422).
- The `smartinfus://device/{serial_number}` URI scheme is scanned from QR codes — extract the serial number, do NOT open it as a web URL (unless deep link handling is added later).
- The backend uses Sanctum token-based auth — tokens are prefixed with `{token_id}|` but the full string including the pipe is the token to send.
- The `device_code` field always refers to `serial_number` (the value in the QR code).
- **403 on the monitor endpoint (`/api/v1/nurse/monitor/by-device-code`) means the user's role is not "nurse".** The app should NOT log them out (they are still authenticated). Show a clear message that only nurse accounts can scan devices, and tell them their current role so they understand why. Let them retry scanning (in case they scanned the wrong QR code) or log out and switch accounts.
- The Start Session form uses `GET /api/v1/nurse/patients` and `GET /api/v1/nurse/beds` for dropdown/autocomplete selectors. These endpoints are scoped to the nurse's organization and only return active records. Do NOT call `GET /api/v1/admin/patients` or `GET /api/v1/admin/beds` — those are admin-only and return 403 for nurses.

---

## Implementation Order (Suggested)

1. **Project setup** — Gradle, Hilt, Retrofit, Navigation setup, theme, base Activity
2. **Auth** — Login screen, TokenManager, AuthInterceptor, auto-login check
3. **Dashboard** — Active sessions list with pull-to-refresh
4. **QR Scanner** — CameraX + ML Kit Barcode Scanning, parse serial number, call monitor endpoint
5. **Start Session** — Form with dropdown selectors for Patient and Bed (populated from `GET /api/v1/nurse/patients` and `GET /api/v1/nurse/beds`), validation, submit
6. **Session Detail** — Full info display + Pause/Complete/Interrupt buttons
7. **Alerts** — List screen + acknowledge action
8. **FCM** — Token registration, push notification handling
9. **Real-time updates (optional)** — polling timer or MQTT client
10. **Polish** — Error handling, empty states, loading indicators, edge cases