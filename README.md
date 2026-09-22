# Forge Market

A Laravel rebuild of the "Software Marketplace UI design" Claude Design project
(`Forge Market`, `Forge Admin`, `Forge Author Dashboard`, `Forge Customer
Dashboard`). It's a standalone app in this repo, independent of the sibling
"Swalaf Yoghurt & Treats" site in `public/`/`includes/`/`sql/` — different
product, different database, no shared code.

## What this is

A software marketplace with four surfaces, all backed by real database
tables (nothing here is hardcoded demo data the way the original mockups
were):

- **Public storefront** (`/`, `/market`, `/market/{product}`, `/hire-us`) —
  browse, search/filter, product detail with reviews, and a "hire the
  studio" quote form.
- **Admin console** (`/admin`) — 14 sections: overview, review queue
  (approve/reject/request changes on author submissions), products,
  authors, customers, orders & refunds, licenses, payouts (run batches,
  hold/release), the studio's service-request pipeline, delivery projects
  (kanban with milestones), support tickets, site content, an
  auto-generated audit log, and settings.
- **Author dashboard** (`/author`) — products, submissions (the review
  workflow from the author's side), sales, payouts, reviews (with replies),
  buyer support, analytics, settings.
- **Customer dashboard** (`/account`) — purchases, downloads, licenses,
  studio service orders, invoices, support, saved items, settings.

Roles live on a single `users` table (`admin` / `author` / `customer`);
`App\Http\Middleware\EnsureRole` gates each dashboard, and policies
(`app/Policies`) scope every query so an author only ever sees their own
products/reviews/tickets and a customer only their own orders/licenses.

## Stack

Laravel 13 + Blade + plain CSS (`public/css/app.css`, translating the
mockups' inline design tokens into real classes) — no Vite/Tailwind/Node
build step, no JS framework. SQLite for local dev; swap `DB_CONNECTION` in
`.env` for MySQL in production.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Seeded logins (password: `password` for all):

- Admin: `admin@forgemarket.test`
- Author (standard tier): `mara@forgemarket.test`
- Customer: `customer@forgemarket.test`

## Payments

Real checkout, both gateways wired end-to-end (`app/Http/Controllers/CheckoutController.php`,
`app/Http/Controllers/Webhooks/`, `app/Services/PaystackClient.php`):

- Customer picks a license and a gateway on `/market/{product}/checkout`,
  gets redirected to Stripe Checkout or Paystack's hosted payment page.
- The **webhook** (not the redirect back) is the source of truth: `checkout.session.completed`
  (Stripe, signature-verified) or `charge.success` (Paystack, HMAC-verified + re-checked against
  their API) fulfills the order — creates the license, increments `sales_count`, emails the
  customer. Fulfillment is wrapped in a row-locked transaction so a retried webhook can't double-fulfill.
- Admin refunds (`/admin/orders`) call the real gateway refund API before flipping the local
  status; a gateway rejection is shown as an error rather than silently marking it refunded anyway.
- **To go live**, create Stripe and/or Paystack accounts and set the keys in `.env`
  (`STRIPE_KEY`/`STRIPE_SECRET`/`STRIPE_WEBHOOK_SECRET`, `PAYSTACK_PUBLIC_KEY`/`PAYSTACK_SECRET_KEY`
  — see the comments above those lines in `.env.example` for where to get them and what webhook
  URL to register). Until keys are set, checkout fails with a clear "not configured" message
  instead of crashing — nothing is silently faked.
- Stripe is priced in USD, Paystack in NGN, independently — there's no currency conversion between
  them. Change `STRIPE_CURRENCY`/`PAYSTACK_CURRENCY` if that split doesn't match your business.

## Notifications

Order confirmations, ticket replies, and review replies are real Laravel notifications
(`app/Notifications/`) sent over the `mail` channel. They'll actually deliver once you set a real
`MAIL_MAILER` (SMTP/Postmark/SES/etc.) in `.env` — the default `MAIL_MAILER=log` just writes them
to the log file, which is fine for local dev.

## Password reset

Full "forgot password" flow (`/forgot-password` → emailed link → `/reset-password/{token}`) using
Laravel's built-in password broker — needs the same real mail configuration as above to actually
deliver the email.

## Tests

```bash
php artisan test
```

Covers role-gating across all three dashboards, an admin approve-and-publish flow, author review
replies (including the cross-author 403 case), a customer can't view another customer's invoice,
password reset end-to-end, and order fulfillment (license creation, notification, and idempotency
against a duplicate webhook delivery).

## Production seeding

`php artisan migrate --seed` is environment-aware: with `APP_ENV=production` it skips the fake
demo catalog entirely and only creates base settings/categories plus one real admin account
(prompts for the email, generates and prints a random password **once** — save it immediately).
Local/staging (`APP_ENV=local`) still gets the full demo catalog described above.

## What's simplified for this pass

- **Downloads** don't serve real build files — there's nowhere to host product binaries in this
  environment, so "Download" just confirms the license and logs the action.
- **Admin "Content" and "Settings"** are simple key/value forms (`App\Models\Setting`), not a
  granular roles/permissions or integrations engine.
- **Custom studio service work** (the "Hire the studio" quote flow) is invoiced manually by an
  admin, not paid through the instant checkout — realistic for bespoke project work, but worth
  knowing it's a deliberate scope boundary, not an oversight.
