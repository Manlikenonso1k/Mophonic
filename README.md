# Mophonik — React + Laravel Filament

A rebuild of the scraped `index.html` / `styles.css` page as a React single-page app
driven by a Laravel + Filament admin panel. Every video and album in the carousel is a
database record, so they can be replaced from the dashboard.

```
utopia-world-travis-scott/
├── assets/     Original images and videos (imported into the DB by the seeder)
├── index.html  The original scraped page, kept for reference
├── styles.css  Its stylesheet, kept for reference
├── backend/    Laravel 13 + Filament 4 (API + /admin dashboard, SQLite)
└── frontend/   React 19 + Vite + Tailwind + Swiper
```

## Run it

**1. Backend** (http://127.0.0.1:8010, admin at `/admin`)

```sh
cd backend
composer install            # first time only
php artisan migrate --seed  # first time only: creates the SQLite DB, imports ../assets
php artisan storage:link    # first time only: exposes uploaded media
php artisan serve --port=8010
```

Local development uses SQLite (`database/database.sqlite`) — nothing to configure.

Login: `victorynonso9@gmail.com` / `utopia1234` (change it, or set `ADMIN_EMAIL` /
`ADMIN_PASSWORD` in `.env` before seeding). More editors:
`php artisan make:filament-user`.

**2. Frontend** (http://localhost:5173)

```sh
cd frontend
npm install               # first time only
npm run dev
```

Vite proxies `/api` and `/storage` to `127.0.0.1:8010`, so nothing else needs
configuring in development. Point it elsewhere with `VITE_BACKEND_ORIGIN`.

## The site

| Route | What it is |
| --- | --- |
| `/` | The carousel — background video per work, stack-and-scale covers |
| `/mophonik/{slug}` | A work's detail page |
| `/shop` | Product grid, filterable by category |
| `/shop/{slug}` | Product detail |
| `/cart` · `/checkout` | Cart and checkout (guest, no accounts) |
| `/shop/thank-you?reference=…` | Order confirmation |

## Managing content

| Screen | What it does |
| --- | --- |
| **Products** | Photo, category, price (entered in naira, stored in kobo), stock, unit, visibility. Drag rows to reorder. |
| **Categories** | Create, rename, reorder, and move products between categories. |
| **Orders** | Every checkout: customer, line items, totals, fulfilment status. Line items are read-only. |
| **Videos & Albums** | One row per carousel item: title, slug, type, cover artwork, background video, order, visibility. Drag rows to reorder. |
| **Subscribers** | Emails captured by the newsletter form in the menu. |
| **Site settings** | Header text, shop/terms links, newsletter heading, delivery fee, and the menu links. |

The dashboard carries three widgets: revenue over time (7/30/90 days), order counts by
status, and the most recent orders.

Sample orders for looking at the dashboard and orders screen:

```sh
php artisan db:seed --class=DemoOrderSeeder
```

That seeder is deliberately not part of `db:seed` — it only runs when you ask for it.

## Payments

Checkout follows the Paystack redirect flow. Amounts are stored and sent in **kobo**;
the server re-prices every cart from its own table, so a tampered client total changes
nothing. Keys live in `.env` only:

```
PAYSTACK_PUBLIC_KEY=
PAYSTACK_SECRET_KEY=
PAYSTACK_PAYMENT_URL=https://api.paystack.co
```

**With no keys set, checkout still works** — the order is recorded and marked as awaiting
manual payment instead of redirecting. Once keys are present, checkout redirects to
Paystack, and `GET /shop/payment/callback` verifies the transaction server-side.

Register `https://your-domain.com/webhooks/paystack` in the Paystack dashboard for
**Test** and again for **Live**. One endpoint handles every event: the signature is
checked with SHA-512 in constant time, unknown references are acknowledged with 200,
and crediting is idempotent, so the callback and the webhook racing cannot double-count
an order or draw stock down twice. Payment moves an order to **New** — the staff queue —
never straight to fulfilled.

Replacing a video or album: open the item, drop a new file into **Cover artwork** or
**Background video** (or paste a URL instead), save, reload the site. An uploaded file
always wins over the URL field, so a URL-backed item can be overridden by uploading.
New items appear on the site as soon as they are marked *Live*.

Uploads land in `backend/storage/app/public/works/…` and are served through the
`public/storage` symlink (`php artisan storage:link` if it is ever missing).

## The API

- `GET /api/site` — settings plus the ordered, visible works (`cover`, `video`, `href`).
- `POST /api/subscribe` — `{ email, terms }`, stores a subscriber.
- `GET /api/shop` — categories, visible products, delivery fee.
- `GET /api/shop/products/{slug}` — one product.
- `POST /api/orders` — `{ customer fields, items: [{product_id, quantity}] }`. Prices are
  never accepted from the client; the server totals the cart itself.
- `GET /api/orders/{reference}` — order confirmation.
- `GET /shop/payment/callback` · `POST /webhooks/paystack` — payment (web routes).

## Tests

```sh
cd backend && php artisan test
```

36 tests. Site: API payload and ordering, upload-wins-over-URL, replacing a cover and a
video through the admin form, subscriber validation, panel access control, every admin
screen, saving site settings. Shop: product visibility and ordering, cart totals from the
price table, duplicate-line merging, stock limits, order creation with snapshotted line
items, client-supplied prices ignored, zero-priced carts rejected, idempotent crediting
with stock drawn down once, webhook signature handling, orders not creatable by hand, and
the order form exposing no line items or totals.

## Deploying

Build the SPA with `npm run build` (set `VITE_API_BASE=https://api.example.com` if the
API lives on another domain) and serve `frontend/dist` with a history fallback so
`/mophonik/:slug`, `/shop/:slug`, `/cart` and `/checkout` all resolve. Set `APP_URL` on
the backend to its public URL — uploaded media URLs are built from it — and
`FRONTEND_URL` to the SPA's URL, which is where the payment callback sends shoppers back.

For the `whitecloudindustry.com` deployment on the same EC2 host as `project_y`, use a
separate Nginx vhost and keep `project_y` untouched. The repo includes:

- `backend/deploy/nginx/whitecloudindustry.com.conf`
- `backend/deploy/apply-whitecloudindustry.sh`

That setup serves this app from `/home/ubuntu/Mophonic/frontend/dist`, proxies `/api`,
`/admin`, and `/storage` to the Laravel backend, and bootstraps a Let's Encrypt cert
with a webroot challenge before enabling HTTPS. It also runs `php artisan migrate --seed`
so the `works` table is populated and the carousel images/videos can render.

**MySQL in production.** No code changes; the migrations run on both drivers. In the
server's `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=utopia
DB_USERNAME=…
DB_PASSWORD=…
ADMIN_EMAIL=…          # the seeder creates this admin account
ADMIN_PASSWORD=…
APP_URL=https://your-domain.com
```

Then `php artisan migrate --seed && php artisan storage:link`. Media uploads live on
the local disk, so use persistent storage (or point the `public` disk at S3).

## Notes on fidelity

- The layout, animations, fonts, and the carousel's stack-and-scale effect are ported
  from the original CSS.
- The wordmark is **set type, not artwork** — the original SVG spells "utopia", so it
  could not survive the rename. It still sits at `frontend/src/assets/utopia-wordmark.svg`;
  swap real Mophonik artwork into `Wordmark.jsx` when you have it. The circular menu
  button still uses the original scrawl mark (`logo-mark.svg`), which is also foreign
  branding and should be replaced.
- The original's Klaviyo SMS ("TEXT") modal was dropped — there is no SMS provider
  behind it. The email signup is wired to the API instead.
- "Explore" opens `/mophonik/{slug}`, a detail page built from the same record.
  Setting **Custom link** on an item sends that button to an external URL instead.
