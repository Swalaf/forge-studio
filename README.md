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

Laravel 13 + Blade + tracked CSS (`public/css/app.css`). The active layouts do
not use Vite, so no Node build is needed to serve the site. SQLite is for local
dev; configure MySQL/MariaDB in production.

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
- **To go live**, create Stripe and optionally Paystack accounts and set the keys in `.env`
  (`STRIPE_KEY`/`STRIPE_SECRET`/`STRIPE_WEBHOOK_SECRET`, `PAYSTACK_PUBLIC_KEY`/`PAYSTACK_SECRET_KEY`
  — see the comments above those lines in `.env.example` for where to get them and what webhook
  URL to register). Until keys are set, checkout fails with a clear "not configured" message
  instead of crashing — nothing is silently faked.
- Catalog prices are USD. Stripe must charge USD; Paystack is shown only when
  configured for USD and the account supports USD charges. NGN is **not** converted
  from the displayed dollar amount; do not enable NGN until separate prices and
  a displayed conversion are implemented.

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

## AlmaLinux / Nginx deployment

These steps assume a single AlmaLinux host, the checkout at `/var/www/forge-market`,
and an Nginx/PHP-FPM service account named `nginx`. Adapt paths, PHP-FPM socket,
and domain to the actual host; the repository cannot configure your Vultr instance.

1. Install Nginx, PHP-FPM and PHP CLI **8.5** (the lockfile needs at least PHP
   8.4.1), Composer, a MySQL/MariaDB PDO driver, and PHP curl, mbstring, XML,
   fileinfo and OpenSSL support from a maintained AlmaLinux-compatible repository.
   Check `php -v`, PHP-FPM's version and `composer check-platform-reqs` on the host.
   Node.js 22.13+ is only needed if you choose to build the unused Vite starter
   assets. Provision a backed-up
   MySQL/MariaDB database, DNS, an HTTPS certificate, and a real SMTP sender.
2. Put the repository under `/var/www/forge-market`, with its private `.env` **outside
   any web-served directory**. On first installation only, copy
   `.env.production.example` to `.env`, fill every placeholder and run
   `php artisan key:generate --no-interaction`. Keep this key unchanged on later
   deployments or existing encrypted sessions/data will become unreadable.
   Use `MAIL_SCHEME=smtp` for port 587 STARTTLS (or `smtps` for implicit TLS).
   Set `SESSION_DOMAIN` to the real site domain (or `null` for host-only cookies),
   and set `APP_URL` to the public HTTPS URL. Keep Paystack keys blank unless
   your account can process the catalog's USD prices.
3. Configure PHP-FPM's pool to run as `nginx`, with a socket accessible to Nginx
   (for example `/run/php-fpm/www.sock`). Make `storage/` and `bootstrap/cache/`
   writable by that pool and by the queue worker; do not make the entire repo
   writable or use `chmod 777`. For example, set the checkout owner/group to
   `deploy:nginx`, give the `nginx` group write access to those two directories,
   and use setgid on their subdirectories so new files inherit the group.
   Keep `.env` readable by the pool/worker but not by other local users.
4. On SELinux-enforcing hosts, label `storage/` and `bootstrap/cache/` as
   `httpd_sys_rw_content_t` with `semanage fcontext` and `restorecon`. Permit
   PHP-FPM outbound database/SMTP/payment HTTPS connections with
   `setsebool -P httpd_can_network_connect 1` when required. Do not disable
   SELinux to solve permission errors.

Install a virtual host like the following **after obtaining the certificate**
(for example with Certbot). Set `server_name`, certificate paths, and `fastcgi_pass`
to the real host values. Only `public/` must be reachable over HTTP:

```nginx
server {
    listen 80;
    server_name market.example.com;
    return 301 https://market.example.com$request_uri;
}

server {
    listen 443 ssl;
    server_name market.example.com;
    root /var/www/forge-market/public;
    index index.php;
    client_max_body_size 110m;

    ssl_certificate /etc/letsencrypt/live/market.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/market.example.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root/index.php;
        fastcgi_pass unix:/run/php-fpm/www.sock;
    }

    location ~ \.php$ { return 404; }
    location ~ /\.(?!well-known) { deny all; }
}
```

Run `nginx -t` before reloading Nginx; start/enable Nginx and PHP-FPM through
systemd. Open ports 80/443 in the host firewall and Vultr firewall, and do not
expose database, PHP-FPM, or queue endpoints publicly.
For studio ZIP uploads, set PHP-FPM's `upload_max_filesize=100M` and
`post_max_size=110M` as well as the Nginx body limit shown above.

On **first installation**, from the project directory, install dependencies
and create schema. `db:seed` must be run from a **private interactive
terminal**: it asks for the real administrator email and displays a one-time
random password unless you set `INITIAL_ADMIN_PASSWORD` in the private `.env`
before seeding. A configured password is hashed and never printed by the seeder;
remove that setting and rebuild the config cache after creating the admin. The
seeder does not change an existing admin's password. Never run it in CI or
capture its output in deployment logs. Change
the seeded `.test` support email in admin settings before launch.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force --no-interaction
php artisan db:seed --force
php artisan optimize --no-interaction
php artisan app:production-readiness-check --no-interaction
```

For each **subsequent release**, keep `.env`, `APP_KEY`, stored product files and
the database intact; reinstall dependencies, run `php artisan migrate
--force --no-interaction`, then `php artisan optimize --no-interaction`,
`php artisan queue:restart --no-interaction`, and the readiness check. Do not
re-run the seed on every release: it also overwrites site settings. If the
active layouts later use `@vite`, build with `npm ci && npm run build` (Node
22.13+) and deploy `public/build/` along with the code (it is gitignored).

Install a persistent worker unit at `/etc/systemd/system/forge-market-queue.service`:

```ini
[Unit]
Description=Forge Market database queue worker
After=network-online.target

[Service]
User=nginx
Group=nginx
WorkingDirectory=/var/www/forge-market
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --backoff=10 --timeout=60 --max-time=3600
Restart=always
RestartSec=5
TimeoutStopSec=90

[Install]
WantedBy=multi-user.target
```

Use `systemctl daemon-reload`, `systemctl enable --now forge-market-queue`,
and inspect `journalctl -u forge-market-queue` after releases. Confirm the
worker uses the same PHP version and `.env` as PHP-FPM; monitor `failed_jobs`
and retry failures after fixing their cause. No application scheduler jobs are
currently defined, so a cron entry for `schedule:run` is not required.

Admin users can create a Studio original draft from Admin > Products and upload
its private ZIP before publishing. Alternatively, place an approved product ZIP at
`storage/app/private/products/{product_id}/{version_slug}.zip` (for example,
product 42 version `1.0.0` is `products/42/1-0-0.zip`). Keep this directory
private and include it in backups; **do not** place commercial ZIPs under
`public/` or any public storage symlink. Checkout refuses to sell software
without the current artifact; purchases serve the ZIP only to an authorized,
non-revoked license holder. Acquisitions require a separate manual handover.
Payout transfers are **not** integrated: the batch button is disabled rather
than falsely marking authors paid. Do not promise automated payouts.

Finally, verify `/up`, login, password-reset email, a test purchase and signed
webhook, authorized download, and HTTPS redirect on the live domain. Register
`/webhooks/stripe` for `checkout.session.completed` and (if enabled)
`/webhooks/paystack` at the gateway dashboards. Back up the database and
`storage/app/private`, monitor logs/queue/failed jobs, and keep `.env` secret.

## What's simplified for this pass

- **Downloads** use operator-provisioned private ZIPs; there is no in-app author
  artifact upload workflow or virus scan yet. Vet files before publishing them.
- **Admin "Content" and "Settings"** are simple key/value forms (`App\Models\Setting`), not a
  granular roles/permissions or integrations engine.
- **Custom studio service work** (the "Hire the studio" quote flow) is invoiced manually by an
  admin, not paid through the instant checkout — realistic for bespoke project work, but worth
  knowing it's a deliberate scope boundary, not an oversight.
