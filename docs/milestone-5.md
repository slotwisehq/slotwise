# Milestone 5 — Analytics dashboard, Outlook sync, self-hosting docs, v1.0

**Status:** Not started
**Depends on:** Milestone 4 complete and merged.

## Goal

The final milestone before tagging v1.0. Fill the remaining product gaps: an analytics
dashboard tenants actually want, Outlook Calendar sync to match Google, and the
self-hosting documentation and tooling needed for the OSS community to run Slotwise
independently. Ship a production-hardened release with a full upgrade path.

---

## Exit criteria

### Analytics dashboard (`pro` + `business` plan)

All analytics are computed from the `appointments` table — no separate analytics store
at this stage. Queries are cached in Redis with a 1-hour TTL per tenant.

- [ ] `GET /admin/analytics` — analytics dashboard page
- [ ] Date range picker: last 7 days / last 30 days / last 90 days / custom range
- [ ] Metrics displayed:
  - Total bookings (confirmed) in range
  - Total revenue (sum of paid appointments)
  - No-show rate (no_show / confirmed × 100)
  - Cancellation rate (cancelled / total × 100)
  - Busiest day of week (bar chart)
  - Busiest hour of day (bar chart)
  - Bookings per service (pie or bar)
  - Bookings per staff member
  - New customers vs returning customers
- [ ] Charts rendered client-side via Chart.js (already available via CDN in the stack)
- [ ] All analytics queries wrapped in `AnalyticsService` — no raw queries in controllers
- [ ] Redis cache key: `analytics:{tenant_id}:{metric}:{date_range_hash}`, TTL 1 hour
- [ ] `Feature::check('analytics_dashboard', $tenant)` gate enforced
- [ ] Pest unit tests for `AnalyticsService` metric calculations (use factories for data)

### Customer management panel

- [ ] `GET /admin/customers` — paginated customer list, searchable by name/email/phone
- [ ] `GET /admin/customers/{customer}` — customer detail:
  - Contact info (editable inline)
  - Booking history (all past + upcoming appointments)
  - Total spend
  - Notes field (freetext, stored in `customers.metadata`)
- [ ] Export customers to CSV: `GET /admin/customers/export`
  - Respects current search/filter state
  - Queued job for large lists (`ExportCustomersJob`) — emails download link when ready
- [ ] Soft-delete customer: anonymises PII in place (`name → "Deleted Customer"`,
      `email → null`, `phone → null`) rather than hard delete (preserves booking history)
- [ ] Pest feature test: export CSV contains correct columns and row count
- [ ] Pest feature test: soft-delete anonymises PII, appointments still exist

### Outlook / Microsoft 365 Calendar sync

Implements the `CalendarSyncDriver` interface defined in Milestone 3. Driver pattern
means no changes to the sync job or booking flow — just a new driver registered
in `config/calendar.php`.

- [ ] Microsoft Azure app registration documented in `docs/integrations/outlook-setup.md`
- [ ] OAuth2 flow via Microsoft Identity Platform (`/admin/staff/{staff}/calendar/connect?provider=outlook`)
- [ ] `OutlookCalendarDriver` implementation using Microsoft Graph API (`microsoft/microsoft-graph`)
- [ ] Token refresh handled (Microsoft tokens expire in 1 hour)
- [ ] `CalendarSyncDriver` resolution: reads `staff_calendar_tokens.provider`, instantiates
      correct driver — no changes needed in sync jobs
- [ ] Admin staff page: shows connected provider (Google / Outlook / None), disconnect button
- [ ] Pest unit test: `OutlookCalendarDriver::createEvent()` called with correct Graph API payload

### Self-hosting documentation and tooling

This is the OSS community's front door. Quality here directly affects stars, forks,
and contributors.

- [ ] `docker-compose.yml` in repo root:
  - Services: `app` (PHP-FPM), `nginx`, `mysql`, `redis`
  - `.env.example` pre-filled with sensible defaults for local dev
  - Volumes for storage and logs
- [ ] `docs/self-hosting.md`:
  - System requirements (PHP 8.2+, MySQL 8+, Redis 6+, Node 20+)
  - Docker Compose quickstart (5-command installation)
  - Manual install (Laravel standard steps, queue worker, scheduler)
  - Nginx / Caddy config snippets for custom domain SSL termination
  - Environment variable reference (every `.env` key explained)
  - Upgrade guide (how to pull new version, run migrations, restart queue)
- [ ] `docs/configuration.md`:
  - Mail driver configuration (SMTP, Mailgun, SES)
  - SMS driver configuration (Twilio, or null to disable)
  - Stripe configuration (keys, webhook secret)
  - Google Calendar OAuth app setup
  - Storage driver (local vs S3-compatible)
- [ ] `Makefile` with convenience targets:
  - `make install` — copy .env.example, install deps, run migrations, seed
  - `make test` — run full Pest suite
  - `make worker` — start queue worker
  - `make lint` — run Pint (PHP) + ESLint (JS)
- [ ] GitHub Actions CI workflow (`.github/workflows/tests.yml`):
  - Runs on every PR
  - PHP 8.2 matrix
  - MySQL service container
  - `php artisan test` must pass
  - Pint + ESLint lint checks

### v1.0 release hardening

- [ ] `CHANGELOG.md` up to date with all changes since v0.1, following Keep a Changelog format
- [ ] `UPGRADE.md` — migration steps for anyone who self-hosted a pre-v1 version
- [ ] All Pest tests passing: `php artisan test --coverage` shows ≥ 80% line coverage
- [ ] No `TODO`, `FIXME`, or `@deprecated` comments in production code paths
- [ ] All routes named consistently (`booking.show`, `admin.services.index`, etc.)
- [ ] All Eloquent relationships documented with PHPDoc `@return` types
- [ ] API rate limits load-tested (document expected throughput in `docs/self-hosting.md`)
- [ ] `php artisan slotwise:health` artisan command: checks DB connection, Redis connection,
      queue worker running, storage writable, mail configured — outputs pass/fail per check
- [ ] GitHub release tagged `v1.0.0` with release notes linking to CHANGELOG
- [ ] Demo instance at `demo.slotwise.app` updated and running v1.0

---

## What comes after v1.0

These are intentionally out of scope for v1.0 but documented here to signal direction
to contributors:

- Mobile apps (React Native or Flutter, consuming the v1 API)
- `castwork/slotwise-filament` — Filament-based admin panel as an optional package
- Group bookings (multiple customers per slot)
- Recurring appointments
- Waiting list when slots are full
- iCal feed per staff member
- Zapier / Make official integrations (beyond webhooks)
- On-premise enterprise licence with priority support SLA

---

## Suggested session breakdown

| Session   | Task                                                                  |
|-----------|-----------------------------------------------------------------------|
| 1         | `AnalyticsService` + Redis caching + Pest unit tests                  |
| 2         | Analytics dashboard Inertia page + Chart.js visualisations            |
| 3         | Customer management panel (list, detail, notes, soft-delete)          |
| 4         | Customer CSV export job                                               |
| 5         | `OutlookCalendarDriver` + Microsoft OAuth flow                        |
| 6         | `docker-compose.yml`, `.env.example`, `Makefile`                      |
| 7         | `docs/self-hosting.md`, `docs/configuration.md`, `UPGRADE.md`         |
| 8         | GitHub Actions CI, `slotwise:health` command, coverage check, release |

---

## Definition of done

`docker-compose up && make install` produces a fully working Slotwise instance in
under 5 minutes on a clean machine. A pro tenant can view their analytics dashboard.
A staff member can sync appointments to Outlook. `php artisan test --coverage` reports
≥ 80% coverage. GitHub release `v1.0.0` is tagged with a complete changelog.
