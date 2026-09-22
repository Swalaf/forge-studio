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

## Tests

```bash
php artisan test
```

Covers role-gating across all three dashboards, an admin approve-and-publish
flow, author review replies (including the cross-author 403 case), and that
a customer can't view another customer's invoice.

## What's simplified for this pass

- **Downloads** don't serve real build files — there's nowhere to host
  product binaries in this environment, so "Download" just confirms the
  license and logs the action.
- **Payments** aren't wired to a real processor — orders/licenses are
  created directly (by the seeder, or would be by a checkout flow this pass
  didn't build) rather than via Stripe/PayPal.
- **Admin "Content" and "Settings"** are simple key/value forms
  (`App\Models\Setting`), not a granular roles/permissions or integrations
  engine.
