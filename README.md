<div style="text-align:center;">

<br />

```
 ____  _       _            _
/ ___|| | ___ | |___      _(_)___  ___
\___ \| |/ _ \| __\ \ /\ / / / __|/ _ \
 ___) | | (_) | |_ \ V  V /| \__ \  __/
|____/|_|\___/ \__| \_/\_/ |_|___/\___|
```

**Open-source appointment & booking engine for service businesses.**  
Self-hostable · Multi-tenant · Laravel 13 · Inertia.js · Vue 3

<br />

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-0F6E56.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20.svg)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D.svg)](https://vuejs.org)
[![PHP](https://img.shields.io/badge/PHP-8.3+-7A86B8.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-Pest_4-16C172.svg)](https://pestphp.com)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-1D9E75.svg)](CONTRIBUTING.md)

<br />

[**Live demo**](https://demo.slotwise.app) · [**Documentation**](docs/) · [**Report a bug**](https://github.com/slotwisehq/slotwise/issues/new?template=bug_report.md) · [**Request a feature**](https://github.com/slotwisehq/slotwise/issues/new?template=feature_request.md)

<br />

</div>

---

## What is Slotwise?

Slotwise is a fully self-hostable, multi-tenant booking engine. Any service business — salon, clinic, tutor, consultant — can run their own Slotwise instance and give customers a clean booking page at their own domain.

It's designed to be:

- **Self-hostable** — run it on your own server with `docker-compose up`. Your data, your rules.
- **Multi-tenant** — one installation supports many businesses, each fully isolated.
- **Developer-friendly** — domain-organised Laravel code, a driver pattern for every integration, and a REST API v1.
- **Open-source first** — the core is free and complete. Cloud hosting is offered for convenience, not as a lock-in strategy.

---

## Screenshots

> _Screenshots coming in v0.1. Run `make install` to see it locally._

---

## Features

### Booking engine
- Public booking pages per business (`/book/{slug}` or custom domain)
- Service picker → staff picker → time slot picker → customer details → confirmation
- Race-condition-safe slot locking (`SELECT … FOR UPDATE`)
- Availability computed from per-staff weekly schedules minus existing appointments
- Confirmation emails with cancellation link

### Tenant admin
- Service, staff, and schedule management
- Booking list with filters (date, staff, status)
- Customer management with booking history
- Analytics dashboard (bookings, revenue, no-show rate, busiest hours)
- Notification log per booking (mail, SMS, calendar sync)

### Integrations
- **Stripe** — subscription billing for plan upgrades; optional upfront payment per booking
- **Stripe Connect** — marketplace mode: take a platform fee on processed payments
- **Twilio** — SMS confirmations, reminders, and cancellation notifications
- **Google Calendar** — per-staff OAuth sync; appointments appear automatically
- **Outlook / Microsoft 365** — same driver interface as Google Calendar
- **Webhooks** — `booking.created`, `booking.cancelled`, `booking.updated` with HMAC-SHA256 signatures

### Developer features
- REST API v1 with Sanctum authentication
- OpenAPI 3.1 spec at `/api/docs` (via Scramble)
- Rate limiting per plan tier
- Feature gates tied to plan (`Feature::check('sms_notifications', $tenant)`)

---

## Quick start (Docker)

```bash
git clone https://github.com/slotwisehq/slotwise.git
cd slotwise
cp .env.example .env
docker-compose up -d
make install
```

Then open [http://localhost:8000](http://localhost:8000).

`make install` runs migrations, seeds the demo tenant (Lumière Salon), and creates a default admin user:

```
Email:    admin@lumieresalon.test
Password: password
```

---

## Manual installation

**Requirements:** PHP 8.2+, MySQL 8+, Redis 6+, Node 20+, Composer 2+

```bash
# 1. Clone and install dependencies
git clone https://github.com/slotwisehq/slotwise.git && cd slotwise
composer install
npm install && npm run build

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env — set DB_*, REDIS_*, MAIL_* at minimum

# 3. Run migrations and seed demo data
php artisan migrate
php artisan db:seed --class=DemoSeeder

# 4. Start the queue worker and scheduler
php artisan horizon
php artisan schedule:work   # dev only — use cron in production
```

See [docs/self-hosting.md](docs/self-hosting.md) for Nginx/Caddy config, SSL, and production checklist.

---

## Configuration

All configuration lives in `.env`. The key variables:

| Variable                                      | Description                                |
|-----------------------------------------------|--------------------------------------------|
| `APP_URL`                                     | Your instance's base URL                   |
| `DB_*`                                        | MySQL connection                           |
| `REDIS_*`                                     | Redis connection (queue + cache)           |
| `MAIL_*`                                      | Mail driver (SMTP, Mailgun, SES…)          |
| `STRIPE_KEY` / `STRIPE_SECRET`                | Stripe API keys (optional)                 |
| `STRIPE_WEBHOOK_SECRET`                       | Stripe webhook signing secret              |
| `TWILIO_SID` / `TWILIO_TOKEN` / `TWILIO_FROM` | SMS via Twilio (optional)                  |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`   | Google Calendar OAuth (optional)           |
| `PLATFORM_FEE_PERCENT`                        | Stripe Connect platform fee (default: `1`) |

Full reference at [docs/configuration.md](docs/configuration.md).

---

## Architecture

Slotwise uses a domain-organised codebase — not the typical Laravel flat `Controllers/` / `Models/` structure:

```
app/
├── Booking/
│   ├── AvailabilityService.php   # Schedule → open slots computation
│   ├── BookingService.php        # Transactional booking creation
│   └── Actions/
│       ├── CreateBooking.php
│       └── CancelBooking.php
├── Tenant/
│   ├── TenantResolver.php        # Domain / subdomain / path resolution
│   └── Concerns/
│       └── BelongsToTenant.php   # Global Eloquent scope trait
├── Notification/
│   ├── Contracts/NotificationDriver.php
│   └── Drivers/MailDriver.php
├── Payment/
│   ├── Contracts/PaymentDriver.php
│   └── Drivers/StripeDriver.php
├── Calendar/
│   ├── Contracts/CalendarSyncDriver.php
│   ├── Drivers/GoogleCalendarDriver.php
│   └── Drivers/OutlookCalendarDriver.php
└── Feature/
    └── Feature.php               # Plan-based feature gate
```

**Multi-tenancy** is handled by [stancl/tenancy](https://tenancyforlaravel.com/) v3 in single-database mode. Every tenant-scoped model carries a `tenant_id` column and uses the `BelongsToTenant` global scope.

**Double-booking prevention** uses a database-level `SELECT … FOR UPDATE` lock inside a transaction — no Redis distributed locks needed.

**Driver pattern** — payments, SMS, and calendar sync are all behind interfaces. Adding a new provider (e.g. Paddle, Vonage, Outlook) means writing one class that implements the interface, then registering it in the config. No changes to the core booking flow.

---

## Plans & feature gates

Slotwise ships with three plan tiers. On a self-hosted instance, every feature is available by default (set `PLAN_ENFORCEMENT=false`). The gates exist for the hosted SaaS tier.

| Feature                       | Free   | Pro       | Business  |
|-------------------------------|--------|-----------|-----------|
| Bookings / month              | 50     | Unlimited | Unlimited |
| Staff members                 | 1      | Unlimited | Unlimited |
| Services                      | 1      | Unlimited | Unlimited |
| SMS notifications             | —      | ✓         | ✓         |
| Custom domain                 | —      | ✓         | ✓         |
| Analytics dashboard           | —      | ✓         | ✓         |
| White-label (remove branding) | —      | —         | ✓         |
| REST API access               | —      | —         | ✓         |
| Stripe Connect                | —      | —         | ✓         |

---

## Contributing

Contributions are very welcome. Slotwise is actively developed and has issues labeled [`good first issue`](https://github.com/slotwisehq/slotwise/labels/good%20first%20issue) if you're looking for somewhere to start.

```bash
# Fork the repo, then:
git clone https://github.com/YOUR_USERNAME/slotwise.git
cd slotwise && make install
git checkout -b feature/your-feature-name

# Run the test suite
make test

# Submit a PR against the `develop` branch
```

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a PR. Key points:

- Write or update Pest tests for any changed behaviour.
- Follow the domain-organised code structure — no flat `Controllers/` dumps.
- New integrations (payment, SMS, calendar) must implement the relevant driver interface.
- PRs targeting `main` directly will be closed — use `develop`.

---

## Roadmap

| Milestone                    | Status       | Description                                          |
|------------------------------|--------------|------------------------------------------------------|
| [v0.1](docs/milestone-1.md)  |  In progress | Core booking flow, single tenant, email confirmation |
| [v0.2](docs/milestone-2.md)  | 📋 Planned   | Multi-tenancy, Stripe billing, feature gates         |
| [v0.3](docs/milestone-3.md)  | 📋 Planned   | Custom domains, SMS (Twilio), Google Calendar sync   |
| [v0.4](docs/milestone-4.md)  | 📋 Planned   | REST API v1, white-label, Stripe Connect, webhooks   |
| [v1.0](docs/milestone-5.md)  | 📋 Planned   | Analytics, Outlook sync, Docker, self-hosting docs   |

Ideas for post-v1.0 (community contributions welcome):
- Group bookings (multiple customers per slot)
- Recurring appointments
- Waiting list
- iCal feed per staff member
- Mobile apps (React Native / Flutter consuming the API)
- Filament admin panel (`slotwise/filament`)

---

## Health check

```bash
php artisan slotwise:health
```

```
✓  Database        Connected (MySQL 8.2.0)
✓  Redis           Connected (127.0.0.1:6379)
✓  Queue worker    Running (3 workers via Horizon)
✓  Storage         Writable
✓  Mail            Configured (smtp / mailpit)
```

---

## Tech stack

| Layer         | Technology                                           |
|---------------|------------------------------------------------------|
| Backend       | PHP 8.2, Laravel 11                                  |
| Frontend      | Vue 3 (Composition API), Inertia.js, Tailwind CSS v4 |
| Database      | MySQL 8+                                             |
| Queue         | Laravel Horizon + Redis                              |
| WebSockets    | Laravel Reverb + Echo                                |
| Testing       | Pest 3                                               |
| Multi-tenancy | stancl/tenancy v3                                    |
| Billing       | Laravel Cashier (Stripe)                             |
| API docs      | dedoc/Scramble (OpenAPI 3.1)                         |

---

## Self-hosting vs. cloud

|                | Self-hosted        | Slotwise Cloud   |
|----------------|--------------------|------------------|
| Cost           | Free               | Paid plans       |
| Data ownership | Yours, fully       | Hosted by us     |
| Updates        | Manual             | Automatic        |
| Custom domain  | You manage SSL     | Included         |
| Support        | Community (GitHub) | Email / priority |
| Stripe Connect | You configure      | Pre-configured   |

The self-hosted version is not feature-limited. You get everything, including features that are plan-gated on the cloud tier.

---

## License

Slotwise is open-source software licensed under the [GNU Affero General Public License v3.0](LICENSE).

The AGPL means: if you run a modified version of Slotwise as a network service, you must make your modifications available under the same license. This protects the project from proprietary forks that offer Slotwise as a hosted service without contributing back.

For a commercial licence that removes the AGPL's network-use requirement, contact [hello@slotwise.app](mailto:hello@slotwise.app).

---

## Credits

Built by [Vasileios Ntoufoudis](https://github.com/ntoufoudis) and [contributors](https://github.com/slotwisehq/slotwise/graphs/contributors).

Inspired by the need for a self-hostable, developer-friendly alternative to Calendly, TidyCal, and Acuity Scheduling.

---

<div style="text-align:center;">

<br />

**[slotwise.app](https://slotwise.app)** · [Twitter/X](https://x.com/slotwiseapp) · [Discussions](https://github.com/slotwisehq/slotwise/discussions)

<br />

_If Slotwise saves you time or money, consider [sponsoring the project](https://github.com/sponsors/slotwisehq)._

</div>
