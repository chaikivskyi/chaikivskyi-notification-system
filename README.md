# Notification System

Laravel-based service for accepting, queueing, and delivering user notifications across multiple channels (email, SMS, push). Built on Laravel 13 with SQS-backed queues, Prometheus metrics, and an OpenAPI spec generated from controller annotations.

## Setup

The project runs entirely in Docker. The `app` service mounts the repo, and `nginx` exposes the HTTP entrypoint on port `8080`.

1. Copy the example env and adjust as needed:
   ```bash
   cp .env.example .env
   ```
2. Build and start the stack:
   ```bash
   docker compose up -d --build
   ```
3. Install PHP dependencies, generate an app key, and run migrations inside the `app` container:
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   docker compose exec app php artisan db:seed
   ```
4. Run a queue worker for the notification queues (in a separate terminal):
   ```bash
   docker compose exec app php artisan queue:work sqs \
       --queue=notifications-high,notifications-normal,notifications-low,default
   ```

Services exposed on the host:

| Service     | URL                                  | Purpose                          |
| ----------- | ------------------------------------ | -------------------------------- |
| App (nginx) | http://localhost:8080                | API + webhook endpoints          |
| API docs    | http://localhost:8080/docs/api       | Scramble-generated Swagger UI    |
| OpenAPI     | http://localhost:8080/docs/api.json  | Raw OpenAPI 3.x spec             |
| Mailpit UI  | http://localhost:8025                | Captured outbound mail           |
| Prometheus  | http://localhost:9090                | Metrics scraper                  |
| LocalStack  | http://localhost:4566                | SQS emulator                     |

## Architecture overview

```
        HTTP API                  Persistence + events                       Queue (SQS)                       Channels
 ┌──────────────────────┐ calls  ┌──────────────────────┐  UserNotification  ┌────────────────────────┐  via() ┌────────────────┐
 │ UserNotification     │ ─────▶ │ UserNotification     │ ─── Created ─────▶ │ DispatchUserNotification│ ─────▶ │ mail (Laravel) │
 │ Controller (API)     │        │ Repository           │      (event)       │ Message (listener)      │        │ SmsChannel     │
 └──────────────────────┘        │                      │                    │   │                     │        │ PushChannel    │
                                 │ store/cancel/        │                    │   ▼                     │        └────────────────┘
                                 │ claim/markDelivered/ │                    │ UserNotificationMessage │
                                 │ markFailed           │                    │ (queued Notification)   │
                                 │                      │                    └────────────────────────┘
                                 │                      │  UserNotificationStatusTransitioned (event)
                                 │                      │ ──────────────────────────────────────┐
                                 └──────────────────────┘                                       ▼
                                                                                     ┌────────────────────────┐
                                                                                     │ RecordUserNotification │
                                                                                     │ Metric (listener)      │
                                                                                     │  → PersistUserNotifi-  │
                                                                                     │    cationMetric (job)  │
                                                                                     └────────────────────────┘
                                                                                                 │
                                                                                                 ▼
                                                                                     ┌────────────────────────┐
                                                                                     │ Prometheus counters    │
                                                                                     │ + delivery latency     │
                                                                                     └────────────────────────┘
```

Key pieces:

- **API layer** (`app/Http/Controllers/Api`) — accepts single and bulk notification requests, returns status, supports cancellation. Validated via `FormRequest` classes under `app/Http/Requests/UserNotification`. The controller delegates to the repository and does **not** dispatch events itself.
- **Repository** (`app/Repositories/UserNotificationRepository.php`) — single source of truth for state transitions (`Accepted → Pending → Delivered | Failed | Canceled`) and the **only** place that dispatches domain events:
  - `UserNotificationCreated` — on `store()` and each row of `storeBulk()`.
  - `UserNotificationStatusTransitioned` — on `cancel()`, `claimForDelivery()`, `markDelivered()`, and `markFailed()`.
- **Listeners** (`app/Listeners`):
  - `DispatchUserNotificationMessage` reacts to `UserNotificationCreated` and dispatches the queued `UserNotificationMessage` notification.
  - `RecordUserNotificationMetric` reacts to `UserNotificationStatusTransitioned` and dispatches `PersistUserNotificationMetric` to record timestamps + Prometheus counters.
- **Queue routing** — notifications are dispatched to one of three SQS queues based on priority: `notifications-high`, `notifications-normal`, `notifications-low` (see `app/Support/Queues.php`).
- **Notification class** (`app/Notifications/UserNotificationMessage.php`) — extends Laravel's `Notification`, picks the channel via `via()`, attaches a correlation-ID middleware, a per-channel rate limiter, and `WithoutOverlapping` for idempotency. `dispatchFor()` also calls `claimForDelivery()` so the `Accepted → Pending` transition is atomic.
- **Channels** (`app/Notifications/Channels`) — `SmsChannel` and `PushChannel` currently **log the payload only**; integrations with real providers are not wired up. Email goes through Laravel's built-in `mail` channel into Mailpit in local dev.
- **Mailpit webhook** (`routes/webhooks.php`, `app/Http/Controllers/Webhooks/MailpitController.php`) — Mailpit posts delivery events back to `/webhooks/mailpit` (HTTP Basic-auth protected via `MAILPIT_WEBHOOK_USER` / `MAILPIT_WEBHOOK_PASSWORD`); the controller calls `markDelivered()` on the repository, which in turn emits `UserNotificationStatusTransitioned`.
- **Metrics** (`app/Prometheus`, `PersistUserNotificationMetric` job) — first-transition timestamps (`queued_at`, `delivered_at`, `failed_at`, `canceled_at`) are recorded per notification, plus a delivery-latency histogram and a status counter exposed at `/metrics`.
- **Correlation IDs** (`app/Http/Middleware/CorrelationId.php`, `PushCorrelationContext` middleware) — propagated from the inbound request, through the queue, into log lines.

## API examples

The full, always-current schema is published at **`/docs/api`** (Swagger UI) and **`/docs/api.json`** (OpenAPI spec). The examples below cover the common flows.

### Create a notification

```bash
curl -X POST http://localhost:8080/api/user-notifications \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "user_id": "0190abcd-0000-7000-8000-000000000001",
    "channel": "email",
    "subject": "Hello",
    "body": "Welcome to the service.",
    "priority": "normal"
  }'
```

`channel` ∈ `email | sms | push`. `priority` ∈ `low | normal | high` (default `normal`). IDs throughout the API are UUID v7 strings.

To delay delivery, add `"scheduled_at": "2026-12-01T15:00:00Z"` (ISO-8601, must be in the future).

### Bulk create

```bash
  curl --request POST \
    --url http://localhost:8080/api/user-notifications/bulk \
    --header 'Accept: application/json' \
    --header 'Content-Type: application/json' \
    --data '{
    "notifications": [
      {
        "user_id": "0190abcd-0000-7000-8000-000000000001",
        "channel": "sms",
        "body": "string",
        "priority": "low"
      },
      {
        "user_id": "0190abcd-0000-7000-8000-000000000001",
        "channel": "email",
        "body": "string",
        "subject": "string",
        "priority": "low"
      }
    ]
  }'
```

Responds with the created notifications, each sharing a generated `batch_id` (UUID v7).

### List with filters

```bash
curl 'http://localhost:8080/api/user-notifications?filters[status]=delivered&filters[channel]=email&per_page=20'
```

Filter keys: `status`, `channel`, `created_from`, `created_to`. Standard Laravel pagination response.

### Status of a single notification or a batch

```bash
curl 'http://localhost:8080/api/user-notifications/status?id={notificationId}'
curl 'http://localhost:8080/api/user-notifications/status?batch_id={batchId}'
```

Exactly one of `id` or `batch_id` is required. For a batch, the response collapses to the least-progressed status (`accepted > pending > failed > canceled > delivered`).

### Cancel a notification

```bash
curl -X PATCH http://localhost:8080/api/user-notifications/{notificationId}/cancel
```

Cancellation is a hard transition to `canceled`; it does **not** retract a job that is already mid-flight.

## Makefile commands

| Target              | Description                                                                 |
| ------------------- | --------------------------------------------------------------------------- |
| `make shell`        | Open a shell inside the `app` container.                                    |
| `make run-tests`    | Run the PHPUnit suite (`./vendor/bin/phpunit`) inside the app.              |
| `make pint`         | Run Laravel Pint to format the codebase (`composer pint`).                  |
| `make phpstan`      | Run PHPStan static analysis (`composer phpstan`, 1G memory limit).          |

## Status of channels

| Channel | Status                              | Notes                                                                 |
| ------- | ----------------------------------- | --------------------------------------------------------------------- |
| Email   | Implemented                         | Routed through Laravel's `mail` channel; Mailpit in local dev.        |
| SMS     | **Not implemented**                 | `SmsChannel` only logs the payload — no provider integration yet.     |
| Push    | **Not implemented**                 | `PushChannel` only logs the payload — no provider integration yet.    |

Wiring SMS and push to real providers (or to a generic webhook-style external provider) is intentionally left as the next integration step.
