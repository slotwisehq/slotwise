# Milestone 2 — Multi-tenancy + Stripe payments

**Status:** Not started
**Depends on:** Milestone 1 complete and merged.

## Goal

Turn the single-tenant prototype into a real multi-tenant system where each business
operates in total isolation. Layer in Stripe billing so tenants can subscribe to a paid
plan. Enforce plan-based feature limits. No custom domains yet (that's Milestone 3).

---

## Exit criteria

### Multi-tenancy (stancl/tenancy v3)

- [ ] `stancl/tenancy` v3 installed, configured in single-database mode
- [ ] `TenantResolver` resolves the current tenant from URL path segment `/{tenant:slug}/...`
      (subdomain resolution comes in Milestone 3 with custom domains)
- [ ] `InitializeTenancyByPath` middleware applied to all public booking and admin routes
- [ ] `BelongsToTenant` global scope now activated via tenancy context (not just trait alone)
- [ ] Pest feature test: authenticated request as Tenant A cannot read Tenant B's appointments,
      services, staff, or customers — all return 404 or 403
- [ ] Pest feature test: creating a booking on Tenant A's slug does not leak into Tenant B

### Tenant registration flow

- [ ] `POST /register` creates a `Tenant` record + owner `User` in a single transaction
- [ ] Slug is auto-generated from business name (unique, URL-safe)
- [ ] New tenant gets `plan = 'free'` by default
- [ ] Owner is redirected to their admin dashboard after registration
- [ ] Validation: business name, slug uniqueness, owner email, password

### Feature gate

- [ ] `Feature` facade / helper: `Feature::check('feature_name', $tenant): bool`
- [ ] Feature definitions live in `config/features.php` — maps feature name → allowed plans
- [ ] Example features to gate from day one:
  - `sms_notifications` → pro, business
  - `custom_domain` → pro, business
  - `analytics_dashboard` → pro, business
  - `white_label` → business
  - `api_access` → business
- [ ] Plan limits (enforced at the service layer, not just UI):
  - free: max 1 staff, max 1 service, max 50 bookings/calendar month
  - pro: unlimited staff and services, unlimited bookings
  - business: everything in pro + white_label + api_access
- [ ] `PlanLimitException` thrown when limit exceeded — caller returns 402 or 422
- [ ] Pest unit tests: feature gate returns correct bool for each plan/feature combination
- [ ] Pest feature test: free tenant attempting to create a 2nd staff member → 422

### Stripe billing (Laravel Cashier)

- [ ] Cashier installed, `Tenant` model is the `Billable` entity
- [ ] `PaymentDriver` interface defined in `app/Payment/Contracts/PaymentDriver.php`
- [ ] `StripeDriver` implements `PaymentDriver`
- [ ] Stripe products and prices created in Stripe dashboard, IDs stored in `config/billing.php`
- [ ] Tenant admin billing page:
  - Shows current plan name and renewal date
  - "Upgrade to Pro" / "Upgrade to Business" buttons → Stripe Checkout session
  - "Manage billing" link → Stripe Customer Portal
- [ ] Webhook handler at `POST /stripe/webhook`:
  - `customer.subscription.created` → update `tenants.plan`
  - `customer.subscription.updated` → update `tenants.plan`
  - `customer.subscription.deleted` → downgrade to `free`
  - `invoice.payment_failed` → send `PaymentFailedMail` to tenant owner
- [ ] Webhook signature verification enabled
- [ ] Pest feature test: webhook `subscription.deleted` downgrades tenant plan to free

### Optional payment step in booking flow

- [ ] Booking flow now has an optional payment step (controlled by tenant setting
      `settings.require_payment_upfront`)
- [ ] When enabled: after customer details form → Stripe Payment Intent → confirmation
- [ ] When disabled: booking created immediately, payment collected at appointment
- [ ] `appointment.payment_status` updated to `paid` on `payment_intent.succeeded` webhook
- [ ] Refund button on booking detail page (admin only) → calls `StripeDriver::refund()`

---

## What is explicitly NOT in scope

- Custom domain resolution (Milestone 3)
- Subdomain-based tenant routing (Milestone 3)
- Stripe Connect / marketplace fee (Milestone 4)
- SMS notifications (Milestone 3)
- Google Calendar sync (Milestone 3)
- API endpoints (Milestone 4)

---

## Suggested session breakdown

| Session  | Task                                                                                 |
|----------|--------------------------------------------------------------------------------------|
| 1        | Install stancl/tenancy, configure single-db mode, write tenant isolation tests first |
| 2        | `TenantResolver`, middleware wiring, tenant registration flow                        |
| 3        | `Feature` gate + plan limits + `PlanLimitException`                                  |
| 4        | Cashier install, `PaymentDriver` interface, `StripeDriver`                           |
| 5        | Stripe Checkout, Customer Portal, billing admin page                                 |
| 6        | Webhook handler, optional payment step in booking flow                               |

---

## Critical: write isolation tests first

Start Session 1 by writing the tenant isolation Pest tests *before* implementing tenancy.
Watch them fail. Then implement until they pass. This is the only reliable way to confirm
isolation is actually working and not just appearing to work on a single-tenant setup.

---

## Definition of done

Two tenants can be registered, each with their own services and bookings, and neither
can access the other's data. A free tenant hitting a plan limit gets a clear error.
A pro tenant can complete a paid booking via Stripe Checkout. Stripe webhooks correctly
update plan status.
