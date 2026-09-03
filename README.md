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

## Managing content

| Screen | What it does |
| --- | --- |
| **Videos & Albums** | One row per carousel item: title, slug, type, cover artwork, background video, order, visibility. Drag rows to reorder. |
| **Subscribers** | Emails captured by the newsletter form in the menu. |
| **Site settings** | Header text, shop/terms links, newsletter heading, and the menu links. |

Replacing a video or album: open the item, drop a new file into **Cover artwork** or
**Background video** (or paste a URL instead), save, reload the site. An uploaded file
always wins over the URL field, so a URL-backed item can be overridden by uploading.
New items appear on the site as soon as they are marked *Live*.

Uploads land in `backend/storage/app/public/works/…` and are served through the
`public/storage` symlink (`php artisan storage:link` if it is ever missing).

## The API

- `GET /api/site` — settings plus the ordered, visible works (`cover`, `video`, `href`).
- `POST /api/subscribe` — `{ email, terms }`, stores a subscriber.

## Tests

```sh
cd backend && php artisan test
```

Covers the API payload and ordering, upload-wins-over-URL, replacing a cover and a
video through the admin form, subscriber validation, panel access control, every admin
screen, and saving site settings.

## Deploying

Build the SPA with `npm run build` (set `VITE_API_BASE=https://api.example.com` if the
API lives on another domain) and serve `frontend/dist` with a history fallback so
`/mophonik/:slug` resolves. Set `APP_URL` on the backend to its public URL —
uploaded media URLs are built from it.

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
