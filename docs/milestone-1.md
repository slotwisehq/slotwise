# Milestone 1 — Core booking flow (single tenant)

**Status:** Not started
**Depends on:** Nothing — this is the foundation.

## Goal

A working end-to-end booking flow. A customer visits a public booking page, picks a
service, picks a time slot, fills in their details, and receives a confirmation email.
No payments, no multi-tenancy enforcement, no real-time updates yet.

The exit criteria below define "done." Do not move to Milestone 2 until every item is checked.

---

## Exit criteria

### Database & models
- [ ] Migrations for all core tables: `tenants`, `users`, `services`, `staff`, `schedules`,
      `appointments`, `customers`
- [ ] All tenant-scoped models use the `BelongsToTenant` trait with a global Eloquent scope
- [ ] `Appointment` model has status enum: `pending`, `confirmed`, `cancelled`, `no_show`
- [ ] `Appointment` model has payment_status enum: `unpaid`, `paid`, `refunded`
- [ ] Soft deletes on `services`, `staff`, `customers`

### AvailabilityService
- [ ] `AvailabilityService::getSlots(int $staffId, int $serviceId, Carbon $date): Collection`
- [ ] Returns open slots as a collection of `Carbon` start times
- [ ] Correctly subtracts existing appointments from schedule windows
- [ ] Respects service `duration_minutes` — no overlapping slots
- [ ] Handles edge case: appointment ending exactly at schedule end time
- [ ] Handles edge case: no schedule defined for a given day → returns empty collection
- [ ] Pest unit tests covering all of the above cases

### BookingService
- [ ] `BookingService::create(array $data): Appointment`
- [ ] Wraps slot check + insert in `DB::transaction()`
- [ ] Uses `Slot::lockForUpdate()` (or equivalent row lock) to prevent double-booking
- [ ] Throws `SlotUnavailableException` if slot is taken — caller handles HTTP response
- [ ] Dispatches `SendBookingConfirmation` queued job after successful creation
- [ ] Pest feature test: concurrent booking attempt on same slot → only one succeeds

### Public booking flow (Inertia pages)
- [ ] `GET /book/{tenant:slug}` → service picker page
- [ ] `GET /book/{tenant:slug}/{service}` → staff picker (skip if only one staff)
- [ ] `GET /book/{tenant:slug}/{service}/{staff}` → slot picker (calendar/date + time grid)
- [ ] `POST /book/{tenant:slug}` → customer details form → booking confirmation page
- [ ] Confirmation page shows: service name, staff name, date/time, customer name
- [ ] All pages use shared layout with tenant name and logo (from `tenants.settings`)
- [ ] Vue components live in `resources/js/Pages/Booking/`

### Tenant admin (Inertia pages, auth-gated)
- [ ] Login page (standard Laravel Breeze or Fortify — pick one and note in CLAUDE.md)
- [ ] Dashboard: upcoming appointments list (today + next 7 days)
- [ ] Services CRUD: name, duration_minutes, price, is_active toggle
- [ ] Staff CRUD: name, bio, avatar upload (stored in `storage/app/public`)
- [ ] Schedules UI: per-staff weekly schedule (day of week, start time, end time)
- [ ] Bookings list: filterable by date, staff, status
- [ ] Booking detail: show customer info, service, time, status; cancel button
- [ ] Vue components live in `resources/js/Pages/Admin/`

### Notifications
- [ ] `SendBookingConfirmation` job dispatched via `database` queue driver (Redis in prod)
- [ ] Sends `BookingConfirmedMail` to customer email
- [ ] Mail template includes: service, staff, date/time, business name, cancel link stub
- [ ] `NotificationDriver` interface stubbed (even if only MailDriver implemented)

### Seeder
- [ ] `DemoSeeder` creates a sample tenant: "Lumière Salon"
- [ ] 2 staff members with Mon–Sat schedules, 9am–6pm
- [ ] 3 services: Haircut (60 min, €45), Colour Treatment (120 min, €90), Blowdry (30 min, €25)
- [ ] 20 past appointments (mix of confirmed and cancelled)
- [ ] 10 upcoming appointments spread over next 14 days
- [ ] Seeder is idempotent (safe to re-run)

### Tests
- [ ] Feature test: full booking flow happy path (GET pages → POST booking → email queued)
- [ ] Feature test: double-booking returns 409
- [ ] Feature test: booking outside schedule hours returns 422
- [ ] Unit test: `AvailabilityService` all edge cases (see above)
- [ ] Unit test: `BookingService::create` dispatches job
- [ ] All tests pass with `php artisan test`

---

## What is explicitly NOT in scope

- Payments of any kind
- Multi-tenancy middleware / tenant resolution from domain or subdomain
- Real-time calendar updates (Reverb/Echo)
- SMS notifications
- Custom domains
- Google Calendar or Outlook sync
- API endpoints (`/api/v1/...`) — that's Milestone 4
- White-labelling / branding customisation beyond tenant name + logo

---

## Suggested session breakdown

Break this milestone into these Claude Code sessions, in order:

| Session   | Task                                                                   |
|-----------|------------------------------------------------------------------------|
| 1         | Migrations, models, `BelongsToTenant` trait, factories                 |
| 2         | `AvailabilityService` + Pest unit tests                                |
| 3         | `BookingService` + double-booking feature test                         |
| 4         | Public booking Inertia pages (service → staff → slot → form → confirm) |
| 5         | Tenant admin Inertia pages (dashboard, services, staff, schedules)     |
| 6         | Notifications, `DemoSeeder`, final test pass                           |

---

## Definition of done

`php artisan test` passes clean. `php artisan db:seed --class=DemoSeeder` completes
without errors. A booking can be completed end-to-end in a browser on a fresh installation.
