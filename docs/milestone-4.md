# Milestone 4 — Public API v1, white-label, Stripe Connect

**Status:** Not started
**Depends on:** Milestone 3 complete and merged.

## Goal

Open Slotwise up to the developer ecosystem. A versioned REST API lets businesses
build their own booking UIs, mobile apps, and Zapier/Make integrations. White-label
removes Slotwise branding for business-tier tenants. Stripe Connect enables the
marketplace model where Slotwise takes a platform cut of processed payments.

---

## Exit criteria

### API v1 (business plan feature)

All API routes live under `/api/v1/`. Authentication via Laravel Sanctum API tokens.
Token management UI in tenant admin settings.

- [ ] `POST /api/v1/auth/token` — create a Sanctum token (accepts email + password)
- [ ] `DELETE /api/v1/auth/token` — revoke current token
- [ ] `GET /api/v1/services` — list active services for the authenticated tenant
- [ ] `GET /api/v1/staff` — list staff members
- [ ] `GET /api/v1/availability?service_id=&staff_id=&date=` — available slots (same logic
      as `AvailabilityService`, returned as ISO 8601 datetime strings)
- [ ] `POST /api/v1/bookings` — create a booking (same validation + lock logic as web flow)
- [ ] `GET /api/v1/bookings` — list bookings (filterable by `?date=&staff_id=&status=`)
- [ ] `GET /api/v1/bookings/{id}` — booking detail
- [ ] `PATCH /api/v1/bookings/{id}` — update status (cancel only via API for now)
- [ ] All responses use dedicated API Resource classes (never raw Eloquent)
- [ ] Rate limiting: 60 req/min on free/pro, 300 req/min on business (via `throttle` middleware)
- [ ] `Feature::check('api_access', $tenant)` enforced — returns 403 with clear message for
      non-business tenants
- [ ] API versioning: `AcceptVersion` header supported but `/api/v1/` path takes precedence
- [ ] OpenAPI 3.1 spec generated via `dedoc/scramble` package — served at `/api/docs`
- [ ] Pest feature tests for every endpoint: happy path, auth failure, plan gate, rate limit

### White-label (`business` plan)

- [ ] `tenants.settings` supports: `hide_powered_by` (bool), `primary_color` (hex),
      `email_from_name`, `email_from_address` (requires to be verified domain)
- [ ] "Powered by Slotwise" footer removed from booking pages when `hide_powered_by = true`
- [ ] Brand colour applied to booking page CTAs and header via CSS custom property
      injected in the Inertia shared data
- [ ] Confirmation emails sent from tenant's own `from` address when configured
- [ ] `Feature::check('white_label', $tenant)` enforced
- [ ] Admin settings page: "Branding" section — colour picker, from-name, from-address fields
- [ ] Pest feature test: booking page HTML does not contain "Slotwise" string when white-label enabled

### Stripe Connect (marketplace model)

This is the platform monetisation mechanism. Tenants connect their own Stripe account;
Slotwise takes a configurable platform fee (default 1%) on each processed payment.

- [ ] `tenants` table: add `stripe_connect_account_id` (nullable)
- [ ] OAuth flow: `GET /admin/billing/connect-stripe` → Stripe Connect onboarding →
      callback stores `stripe_connect_account_id`
- [ ] `StripeDriver` updated: when tenant has `stripe_connect_account_id`, create
      Payment Intent with `application_fee_amount` set to `platform_fee_percent × amount`
      and `transfer_data.destination` set to tenant's Connect account
- [ ] Platform fee percentage stored in `config/billing.php` (`platform_fee_percent`)
- [ ] Admin billing page: shows Connect status — not connected / connected (with Stripe
      dashboard link) / payouts paused (shows reason from Stripe)
- [ ] `account.updated` webhook from Stripe: sync Connect account status to tenant record
- [ ] When Connect not configured: payments go to the Slotwise platform account (existing behaviour)
- [ ] Pest feature test: Payment Intent created with correct `application_fee_amount`
      when Connect account is configured

### Zapier / Make webhook support

- [ ] `tenant_webhooks` table: `id, tenant_id, url, events (json array), secret, is_active`
- [ ] Admin settings: webhook management UI (add, list, delete, show secret, test)
- [ ] Events dispatched: `booking.created`, `booking.cancelled`, `booking.updated`
- [ ] `DispatchWebhook` queued job: POST to webhook URL with HMAC-SHA256 signature header
      (`X-Slotwise-Signature`)
- [ ] Retry logic: 3 attempts with exponential backoff on non-2xx response
- [ ] Webhook delivery log: `webhook_deliveries` table — `id, webhook_id, event, payload,
      response_status, attempt_count, delivered_at`
- [ ] Admin: delivery log viewable per webhook, with manual retry button
- [ ] Pest feature test: `booking.created` webhook dispatched with correct payload and signature

---

## What is explicitly NOT in scope

- GraphQL API (not planned)
- Mobile SDKs (community contribution opportunity — document the REST API well)
- Outlook Calendar sync (Milestone 5)
- Analytics dashboard (Milestone 5)
- Self-hosted Docker image and deployment docs (Milestone 5)

---

## Suggested session breakdown

| Session   | Task                                                                      |
|-----------|---------------------------------------------------------------------------|
| 1         | Sanctum setup, token management UI, API route skeleton, plan gate         |
| 2         | Services, staff, availability API endpoints + Resources + tests           |
| 3         | Bookings API endpoints (list, detail, create, cancel) + tests             |
| 4         | Rate limiting, OpenAPI spec via Scramble, `/api/docs` page                |
| 5         | White-label settings, colour injection, email from-address, feature gate  |
| 6         | Stripe Connect OAuth + StripeDriver update + Connect status UI            |
| 7         | Webhook table, `DispatchWebhook` job, retry logic, delivery log, admin UI |

---

## Definition of done

A business-tier tenant can: (a) generate an API token and retrieve their availability
slots via `GET /api/v1/availability`, (b) hide Slotwise branding and use their own
brand colour on the booking page, (c) connect their Stripe account and receive payments
directly with a platform fee deducted, (d) configure a webhook and receive
`booking.created` events in Zapier. OpenAPI docs are publicly accessible at `/api/docs`.
