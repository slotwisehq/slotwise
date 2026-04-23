# Milestone 3 — Custom domains, SMS notifications, Google Calendar sync

**Status:** Not started
**Depends on:** Milestone 2 complete and merged.

## Goal

The three features that turn Slotwise from a functional tool into a polished product
businesses actually want to use publicly. Custom domains let a salon send customers to
`book.lumieresalon.com` instead of a Slotwise-branded URL. SMS dramatically reduces
no-shows. Google Calendar sync prevents double-bookings from the staff side.

All three are `pro` plan features (gated via `Feature::check()`).

---

## Exit criteria

### Custom domains

- [ ] `tenants` table: add `custom_domain` (nullable, unique string)
- [ ] `TenantResolver` extended: resolve tenant by `custom_domain` when request host
      does not match the Slotwise root domain
- [ ] Resolution priority: custom domain → subdomain (`{slug}.slotwise.app`) → path segment
- [ ] Admin settings page: "Custom domain" input field
  - Validates domain format
  - Shows DNS instructions (CNAME `book.yourdomain.com → booking.slotwise.app`)
  - Shows verification status: unverified / verified
- [ ] Domain verification: `DomainVerificationJob` (queued, runs every 10 min per unverified
      domain) — does a DNS CNAME lookup, sets `custom_domain_verified_at` on match
- [ ] SSL: document that operators use a reverse proxy (Caddy or Nginx) with Let's Encrypt
      for self-hosters; the SaaS tier handles this at the infrastructure level (out of scope
      for this milestone — add a note in `docs/self-hosting.md`)
- [ ] `Feature::check('custom_domain', $tenant)` gate enforced before saving
- [ ] Pest feature test: request with verified custom domain resolves correct tenant
- [ ] Pest feature test: unverified custom domain falls through to 404

### Subdomain routing

- [ ] `{slug}.slotwise.app` routes to the correct tenant booking page
- [ ] Wildcard subdomain configured in `config/tenancy.php`
- [ ] Works alongside custom domain resolution (custom domain takes priority)
- [ ] Pest feature test: subdomain resolves correct tenant

### SMS notifications (driver pattern)

- [ ] `SmsDriver` interface in `app/Notification/Contracts/SmsDriver.php`:
  ```
  send(string $to, string $message): void
  ```
- [ ] `TwilioSmsDriver` implementation using `twilio/sdk`
- [ ] `NullSmsDriver` for local dev / self-hosters who don't configure SMS
- [ ] `config/sms.php`: `driver` key (default: `null`), Twilio credentials
- [ ] SMS sent on these events (all queued jobs):
  - Booking confirmed → customer receives confirmation SMS
  - Reminder → customer receives SMS 24 hours before appointment
  - Booking cancelled → customer receives cancellation SMS
- [ ] `AppointmentReminderJob` scheduled via Laravel Scheduler — runs hourly, queries
      appointments starting in 23–25 hours, dispatches reminder SMS job for each
- [ ] Admin settings: enable/disable SMS notifications per tenant; requires phone number
      on customer record (gracefully skipped if missing)
- [ ] `Feature::check('sms_notifications', $tenant)` enforced before sending
- [ ] Pest unit test: `TwilioSmsDriver` called with correct params on booking creation
- [ ] Pest feature test: SMS skipped (not queued) when feature not enabled for plan

### Google Calendar sync

- [ ] OAuth2 flow: staff member can connect their Google account via
      `GET /admin/staff/{staff}/calendar/connect` → Google OAuth → callback stores tokens
- [ ] Tokens stored encrypted in `staff_calendar_tokens` table:
      `id, staff_id, provider (google|outlook), access_token, refresh_token, expires_at`
- [ ] `CalendarSyncDriver` interface in `app/Calendar/Contracts/CalendarSyncDriver.php`:
  ```
  createEvent(Appointment $appointment): string  // returns provider event ID
  updateEvent(Appointment $appointment): void
  deleteEvent(Appointment $appointment): void
  ```
- [ ] `GoogleCalendarDriver` implementation using `google/apiclient`
- [ ] `NullCalendarDriver` for staff who haven't connected
- [ ] On appointment created → `SyncAppointmentToCalendar` queued job
- [ ] On appointment cancelled → `RemoveAppointmentFromCalendar` queued job
- [ ] Token refresh handled automatically in driver (check `expires_at`, refresh if needed)
- [ ] `staff_calendar_tokens` stores `provider_event_id` per appointment (for update/delete)
- [ ] Admin staff detail page: "Connect Google Calendar" button / connected status / disconnect
- [ ] Pest unit test: driver called with correct appointment data on booking creation
- [ ] Pest feature test: disconnect removes token record, subsequent sync uses NullDriver

### Notifications log

- [ ] `notification_logs` table: `id, tenant_id, appointment_id, channel (mail|sms|calendar),
      event (confirmed|reminder|cancelled), status (queued|sent|failed), sent_at, error`
- [ ] Every notification attempt (mail, SMS, calendar sync) writes a log record
- [ ] Admin booking detail page: shows notification history for that appointment
- [ ] Failed notifications visible in admin — allows manual retry button
      (dispatches the job again)

---

## What is explicitly NOT in scope

- Outlook / Microsoft 365 Calendar sync (driver interface is ready, implementation is Milestone 5)
- Stripe Connect / marketplace (Milestone 4)
- API endpoints (Milestone 4)
- White-label email templates (Milestone 4)
- Analytics dashboard (Milestone 5)

---

## Suggested session breakdown

| Session   | Task                                                                             |
|-----------|----------------------------------------------------------------------------------|
| 1         | `TenantResolver` extended for subdomain + custom domain; `DomainVerificationJob` |
| 2         | Admin custom domain UI; DNS instruction copy; verification status display        |
| 3         | `SmsDriver` interface + `TwilioSmsDriver` + `NullSmsDriver`; config; jobs        |
| 4         | `AppointmentReminderJob` scheduler; SMS gating; unit + feature tests             |
| 5         | Google OAuth flow; token storage; `CalendarSyncDriver` + `GoogleCalendarDriver`  |
| 6         | Sync jobs; notification_logs table; admin notification history UI                |

---

## Definition of done

A pro tenant can: (a) set a custom domain and have bookings resolve correctly on it,
(b) enable SMS and have customers receive confirmation and reminder texts via Twilio,
(c) connect a staff member's Google Calendar and see new appointments appear there
automatically. All notification attempts are logged and visible in the admin.
