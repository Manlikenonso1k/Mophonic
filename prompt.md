This project is "Utopia World" — a React 19 + Vite + Tailwind SPA on a Laravel 13 +
Filament 4 backend. Read README.md first for the architecture. Two tasks: a rename,
and a full shop feature.

--- READ THESE BEFORE WRITING ANY CODE ---

- FILAMENT_UI.md — how to customize Filament in this project. Follow it. Do not
  guess at Filament 4 theming or discover it by trial and error.
- PAYMENTS_INTEGRATION.md — the payment integration pattern to use for checkout.
- index.html and styles.css in the project root — the original scraped page. This
  is the visual source of truth for the "rock" aesthetic: dark, high-contrast,
  large type, the stack-and-scale carousel feel.

Also use my installed UI/UX skill for the frontend design work.

--- TASK 1: RENAME TO MOPHONIK ---

Replace "Utopia World" / "utopia-world" / "utopia" with "Mophonik" / "mophonik"
throughout. Be systematic — grep for all case variants before editing. Cover at
minimum: app name and APP_NAME, package.json names, the Filament panel brand,
seeder data, site settings defaults, page titles and meta, and README.md.

The detail route is currently /utopia-world/:slug — rename it to /mophonik/:slug
and update the React router, the "Explore" button, and the deploy note in README
about the history fallback.

Do not rename the DB_DATABASE value in any committed .env.example without telling
me — flag it instead so I decide.

--- TASK 2: SHOP ---

Build a shop for food products, visually continuous with the homepage. Same dark
rock aesthetic, same typography and motion language — it should read as the same
site, not a bolted-on store.

Frontend pages:
1. Shop — product grid, filterable by category. Product cards with image, name,
   price, add-to-cart.
2. Product detail — larger imagery, description, quantity, add-to-cart.
3. Cart — line items, quantity adjust, remove, running total.
4. Checkout — customer details, order summary, payment via the integration in
   PAYMENTS_INTEGRATION.md.

Backend:
- Migrations and models for Category, Product, Order, OrderItem. Products need
  image upload (same storage/app/public pattern as works), price, description,
  stock, category, visibility, sort order.
- Seed realistic dummy food products across a few categories, with placeholder
  images, so I can see the design working immediately.
- API endpoints following the existing /api/site convention.

Filament admin — custom UI, NOT stock Filament styling:
- Products resource: image upload, category, price, stock, visibility, drag to
  reorder. Match the pattern already used by the Videos & Albums resource.
- Categories resource: create, rename, reorder, assign products.
- Orders resource: order list with status, customer details, line items, totals.
  Read-only line items — staff shouldn't be able to fabricate an order's contents.
- Dashboard widgets: sales overview (revenue over time), recent orders, and order
  count by status.

Theme the panel per FILAMENT_UI.md so it carries the site's identity rather than
default Filament blue.

--- CONSTRAINTS ---

- Don't break the existing carousel, /api/site, /api/subscribe, or the works
  admin screen. Run `php artisan test` when you're done — the existing suite
  covers those and must still pass.
- Write tests for the new work too: cart totals, order creation, product API
  ordering and visibility, and admin access control on the orders screen.
- Payment credentials go in .env only. Never commit a key.

--- ORDER OF WORK ---

Do the rename first and confirm the app still boots and tests pass. Then show me
the shop data model before building the UI, so I can correct the schema before
you've built four pages on top of it.