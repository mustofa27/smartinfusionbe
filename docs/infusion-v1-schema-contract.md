# Infusion Monitoring V1 Schema Contract

Status: Draft for review (no implementation yet)
Date: 2026-05-22

## 1) Agreed Scope

- Multi-organization architecture from day one.
- Roles: admin and nurse.
- Device ingestion path: device -> MQTT broker (mqtt.icminovasi.my.id) -> backend consumer.
- Nurse/Admin app auth: Laravel Sanctum.
- Push notifications: FCM only.
- Dedicated patient table included.
- Nurse monitoring starts by device unique code from app.
- If device has no active infusion session, nurse fills start-session form from app.
- Multiple nurses may monitor the same device.
- Alerts auto-resolve when condition clears.
- One active infusion session per patient.

## 2) Conventions

- Primary key: `id` bigint unsigned auto-increment.
- Multi-tenant scope: all business tables include `organization_id` unless globally shared.
- Timestamps: `created_at`, `updated_at` on most tables.
- Soft deletes: not enabled in V1 unless specified.
- Time fields use UTC in DB.
- Use FK constraints for integrity; use app-level checks for workflow rules.

## 3) Enums (DB values)

### users.role
- admin
- nurse

### devices.status
- online
- offline
- maintenance
- retired

### beds.status
- active
- inactive
- maintenance

### infusion_sessions.status
- active
- paused
- completed
- interrupted

### alerts.severity
- info
- warning
- critical

### alerts.status
- open
- acknowledged
- resolved

### alert_deliveries.channel
- fcm

### alert_deliveries.delivery_status
- queued
- sent
- failed

## 4) Table Contract

## organizations

Columns:
- id
- name varchar(150) not null
- code varchar(50) not null
- timezone varchar(64) not null default 'UTC'
- is_active boolean not null default true
- created_at, updated_at

Indexes and constraints:
- unique: code
- index: is_active

## users (extend default)

Columns:
- id
- organization_id bigint unsigned not null
- name varchar(120) not null
- email varchar(190) not null
- email_verified_at timestamp nullable
- password varchar(255) not null
- role enum(admin,nurse) not null
- phone varchar(30) nullable
- is_active boolean not null default true
- last_login_at timestamp nullable
- remember_token varchar(100) nullable
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- unique composite: (organization_id, email)
- index: role
- index: is_active

Notes:
- Global unique email is not used to allow same email across organizations if needed.

## wards

Columns:
- id
- organization_id bigint unsigned not null
- name varchar(120) not null
- floor varchar(30) nullable
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- unique composite: (organization_id, name)

## rooms

Columns:
- id
- ward_id bigint unsigned not null
- room_number varchar(40) not null
- created_at, updated_at

Indexes and constraints:
- fk: ward_id -> wards.id
- unique composite: (ward_id, room_number)

## beds

Columns:
- id
- room_id bigint unsigned not null
- bed_number varchar(40) not null
- status enum(active,inactive,maintenance) not null default active
- created_at, updated_at

Indexes and constraints:
- fk: room_id -> rooms.id
- unique composite: (room_id, bed_number)
- index: status

## patients

Columns:
- id
- organization_id bigint unsigned not null
- medical_record_no varchar(80) not null
- full_name varchar(160) not null
- gender varchar(20) nullable
- date_of_birth date nullable
- notes text nullable
- is_active boolean not null default true
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- unique composite: (organization_id, medical_record_no)
- index: full_name
- index: is_active

## devices

Columns:
- id
- organization_id bigint unsigned not null
- serial_number varchar(120) not null
- mqtt_topic varchar(255) not null
- model varchar(80) nullable
- firmware_version varchar(80) nullable
- status enum(online,offline,maintenance,retired) not null default offline
- last_seen_at timestamp nullable
- metadata json nullable
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- unique composite: (organization_id, serial_number)
- unique: mqtt_topic
- index: status
- index: last_seen_at

## device_bed_assignments

Columns:
- id
- organization_id bigint unsigned not null
- device_id bigint unsigned not null
- bed_id bigint unsigned not null
- mounted_at timestamp not null
- unmounted_at timestamp nullable
- mounted_by_user_id bigint unsigned nullable
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: device_id -> devices.id
- fk: bed_id -> beds.id
- fk: mounted_by_user_id -> users.id
- index composite: (device_id, unmounted_at)
- index composite: (bed_id, unmounted_at)

Workflow rule (app-level):
- Only one active assignment per device where unmounted_at is null.
- Only one active assignment per bed where unmounted_at is null.

## infusion_sessions

Columns:
- id
- organization_id bigint unsigned not null
- patient_id bigint unsigned not null
- device_id bigint unsigned not null
- bed_id bigint unsigned not null
- started_by_user_id bigint unsigned nullable
- ended_by_user_id bigint unsigned nullable
- fluid_name varchar(120) not null
- bag_volume_ml decimal(8,2) not null
- bag_empty_weight_grams decimal(10,2) not null
- initial_weight_grams decimal(10,2) not null
- fluid_density_g_per_ml decimal(6,4) not null default 1.0000
- started_at timestamp not null
- ended_at timestamp nullable
- status enum(active,paused,completed,interrupted) not null default active
- notes text nullable

Live denormalized fields:
- last_weight_grams decimal(10,2) nullable
- last_remaining_ml decimal(10,2) nullable
- last_flow_ml_per_hour decimal(10,2) nullable
- last_reading_at timestamp nullable

Snapshot fields (optional but recommended for historical consistency):
- patient_name_snapshot varchar(160) nullable
- mrn_snapshot varchar(80) nullable
- bed_label_snapshot varchar(120) nullable

- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: patient_id -> patients.id
- fk: device_id -> devices.id
- fk: bed_id -> beds.id
- fk: started_by_user_id -> users.id
- fk: ended_by_user_id -> users.id
- index composite: (organization_id, status)
- index composite: (device_id, status)
- index composite: (patient_id, started_at)
- index: last_reading_at

Workflow rule (app-level):
- One active session per device.
- One active session per patient.

## infusion_readings

Columns:
- id
- organization_id bigint unsigned not null
- infusion_session_id bigint unsigned not null
- device_id bigint unsigned not null
- measured_weight_grams decimal(10,2) not null
- remaining_ml decimal(10,2) not null
- flow_ml_per_hour decimal(10,2) nullable
- battery_percent tinyint unsigned nullable
- signal_quality varchar(40) nullable
- recorded_at timestamp not null
- received_at timestamp not null
- raw_payload json nullable
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: infusion_session_id -> infusion_sessions.id
- fk: device_id -> devices.id
- index composite: (infusion_session_id, recorded_at)
- index composite: (device_id, recorded_at)
- index composite: (organization_id, recorded_at)

Retention note:
- Keep this append-only; no updates in normal operation.

## alert_rules

Columns:
- id
- organization_id bigint unsigned not null
- code varchar(50) not null
- threshold_value decimal(12,4) not null
- threshold_unit varchar(30) not null
- cooldown_seconds int unsigned not null default 300
- is_active boolean not null default true
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- unique composite: (organization_id, code)
- index: is_active

V1 rule codes:
- low_volume
- no_flow
- device_offline

## alerts

Columns:
- id
- organization_id bigint unsigned not null
- infusion_session_id bigint unsigned nullable
- patient_id bigint unsigned nullable
- device_id bigint unsigned not null
- alert_type varchar(50) not null
- severity enum(info,warning,critical) not null
- message varchar(255) not null
- triggered_at timestamp not null
- acknowledged_at timestamp nullable
- acknowledged_by_user_id bigint unsigned nullable
- resolved_at timestamp nullable
- resolved_by_user_id bigint unsigned nullable
- status enum(open,acknowledged,resolved) not null default open
- dedupe_key varchar(120) nullable
- payload json nullable
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: infusion_session_id -> infusion_sessions.id
- fk: patient_id -> patients.id
- fk: device_id -> devices.id
- fk: acknowledged_by_user_id -> users.id
- fk: resolved_by_user_id -> users.id
- index composite: (organization_id, status, triggered_at)
- index composite: (device_id, status)
- index: dedupe_key

## nurse_device_subscriptions

Purpose:
- Defines which nurses should receive alerts for which devices.
- Multiple nurses can subscribe to the same device.

Columns:
- id
- organization_id bigint unsigned not null
- nurse_user_id bigint unsigned not null
- device_id bigint unsigned not null
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: nurse_user_id -> users.id
- fk: device_id -> devices.id
- unique composite: (nurse_user_id, device_id)
- index composite: (organization_id, nurse_user_id)

## nurse_fcm_tokens

Purpose:
- Stores Android FCM registration tokens per nurse device.

Columns:
- id
- organization_id bigint unsigned not null
- nurse_user_id bigint unsigned not null
- fcm_token varchar(255) not null
- app_version varchar(40) nullable
- device_os varchar(20) nullable
- device_model varchar(80) nullable
- last_seen_at timestamp nullable
- is_active boolean not null default true
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: nurse_user_id -> users.id
- unique: fcm_token
- index composite: (nurse_user_id, is_active)

## alert_deliveries

Columns:
- id
- organization_id bigint unsigned not null
- alert_id bigint unsigned not null
- user_id bigint unsigned not null
- channel enum(fcm) not null default fcm
- fcm_token varchar(255) nullable
- sent_at timestamp nullable
- delivery_status enum(queued,sent,failed) not null default queued
- provider_message_id varchar(190) nullable
- error_message varchar(255) nullable
- created_at, updated_at

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: alert_id -> alerts.id
- fk: user_id -> users.id
- index composite: (alert_id, delivery_status)
- index composite: (user_id, created_at)

## audit_logs

Columns:
- id
- organization_id bigint unsigned not null
- actor_user_id bigint unsigned nullable
- action varchar(80) not null
- entity_type varchar(80) not null
- entity_id bigint unsigned nullable
- before_json json nullable
- after_json json nullable
- ip_address varchar(45) nullable
- user_agent varchar(255) nullable
- created_at timestamp not null

Indexes and constraints:
- fk: organization_id -> organizations.id
- fk: actor_user_id -> users.id
- index composite: (organization_id, created_at)
- index composite: (entity_type, entity_id)

## 5) MQTT Topic and Payload Contract (V1)

Topic format:
- `smart-infusion/{device_id}/weight`

Reading payload:
- raw numeric weight only, for example `612.45`

Validation rules (consumer):
- Reject non-numeric payloads.
- Reject negative weight.
- Resolve device by matching `{device_id}` topic segment to `devices.mqtt_topic` (example: `device_1-c51c`).

## 6) API Contract Skeleton (V1)

Auth:
- POST /api/v1/auth/login
- POST /api/v1/auth/logout

Nurse app:
- GET /api/v1/nurse/dashboard
- POST /api/v1/nurse/monitor/by-device-code
- GET /api/v1/nurse/infusion-sessions/active
- GET /api/v1/nurse/alerts
- POST /api/v1/nurse/alerts/{id}/acknowledge
- POST /api/v1/nurse/fcm-tokens

Behavior notes:
- `POST /api/v1/nurse/monitor/by-device-code` resolves device by unique code and subscribes nurse to monitor that device.
- If no active infusion session exists for the device, API returns a `session_required` response and required form fields for session creation.
- Nurse can then call session start endpoint with patient and infusion fields.

Admin:
- CRUD organizations (super-admin later, optional)
- CRUD wards/rooms/beds
- CRUD patients
- CRUD devices
- POST /api/v1/admin/device-assignments
- POST /api/v1/admin/infusion-sessions/start
- POST /api/v1/admin/infusion-sessions/{id}/stop
- CRUD alert rules

Nurse session creation (if permitted in policy):
- POST /api/v1/nurse/infusion-sessions/start

## 7) Performance and Operations Notes

- ingestion path is event-driven; no scheduler needed for reading collection.
- Index `infusion_readings` by session/time for timeline queries.
- Consider monthly partitioning if write volume becomes large.
- Use queue workers for alert evaluation + FCM sending to keep ingestion fast.

## 8) Alert Dedupe and Auto-Resolve Rules

`dedupe_key` meaning:
- A unique key to avoid creating repeated identical alerts in a short period.

Recommended formula:
- `organization_id + device_id + alert_type + time_window_bucket`

Example:
- low_volume alert for device 42 in a 5-minute window -> one open alert only, not one per reading.

Auto-resolve behavior:
- When readings return to normal for a configurable clear duration, set alert to `resolved` automatically.
- Keep `resolved_at` and `resolved_by_user_id` nullable; for auto-resolve set `resolved_by_user_id` to null and include reason in payload.

## 9) Calibration Strategy (Recommended)

Decision:
- Calibration should be handled primarily on the device, with backend storing calibration metadata and history.

Why device-side primary calibration is better:
- Lower latency and stable conversion even if network is down.
- Sensor-specific coefficients are closest to hardware behavior.
- Avoids backend recomputation complexity for every raw sample.

Backend responsibilities:
- Store current calibration profile per device (`tare_weight_grams`, `scale_factor`, `version`, `calibrated_at`).
- Validate outlier readings and flag suspected miscalibration.
- Keep audit history of calibration changes.

Fallback:
- Allow backend-side correction factor override for emergency use, but this should be temporary and auditable.

## 10) Review Status

Locked decisions:
1. Nurse monitoring is by device unique code.
2. Alerts use auto-resolve.
3. One active infusion session per patient.
4. Dedupe uses rule/device/time-window keying.
5. Calibration is device-side primary, backend stores and audits.

Implementation can now start with migrations and Sanctum setup.
