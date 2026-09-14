Five fixes for the shop. Do them in this order and report after each.

────────────────────────────────
1. BUG: Add to cart only works for some products on mobile
────────────────────────────────
On mobile viewports, only "Bento Food" and "Palm Wine" successfully add to
cart. Other products do nothing when tapped. Desktop works for all products.

Diagnose before fixing. The selective nature is the key clue — compare the
working products against the failing ones and report the difference:
- Do the working ones differ in data? (category, options/variants, stock
  status, image present vs missing, price format, slug characters)
- Do their cards differ in rendered markup or height?
- Are the failing cards' buttons overlapped by another element at mobile
  breakpoints? Check computed hit areas at 375px, not visual appearance.
- Is the product grid virtualised or lazy-loaded, so handlers are only
  bound to initially-visible cards?
- Is there a JS error thrown on the first failing product that halts
  handler binding for the rest?

Report the actual root cause before showing the fix. Do not apply a
speculative fix.

────────────────────────────────
2. BUG: Successful payment redirects to homepage
────────────────────────────────
After Paystack confirms payment the user lands on the homepage. It must
land on an order confirmation page showing the order reference, items,
totals, and delivery details.

- Fix the Paystack callback redirect to route to /order/{reference} (or
  equivalent), not /.
- The confirmation page must load from the verified order server-side, not
  from cart state — cart is cleared by then.
- Handle the case where the user closes the browser and returns later: the
  page must still resolve from the reference.
- Do not move payment verification to the frontend. The webhook remains
  the source of truth.

────────────────────────────────
3. Customer receipt (downloadable)
────────────────────────────────
- On the confirmation page, a "Download receipt" button producing a PDF.
- Contents: business name and contact, order reference, date/time, customer
  name and phone, delivery address, line items with quantities and unit
  prices, subtotal, delivery fee, total, payment status, gateway reference.
- Generate server-side. Use a maintained PDF package compatible with this
  Laravel version.
- Access rule: receipt is retrievable by order reference only. Use a
  signed URL or a non-guessable token — do NOT make sequential order IDs
  publicly fetchable, or anyone can enumerate other customers' receipts.
- Only generate a receipt for orders with a confirmed paid status.

────────────────────────────────
4. Staff receipt view (super_admin and waiter)
────────────────────────────────
- In the Filament panel, a receipt view on the Order resource showing the
  same information plus internal fields (payment channel, gateway response,
  timestamps).
- Visible to both super_admin and waiter. Waiter is read-only — view and
  print/download only, no edit, no refund action, no delete.
- Gate this by permission, not by role name string.
- Add a print-friendly layout suitable for a receipt printer if one is in
  use; otherwise A4/thermal-width CSS.

────────────────────────────────
5. Customer feedback animations
────────────────────────────────
- On successful "add to cart": a brief toast confirming the item was added,
  plus a small celebratory animation. Must not block interaction or shift
  layout.
- On confirmed successful payment (confirmation page load): a confetti
  animation with a clear success message.
- Respect prefers-reduced-motion — skip animation entirely for users who
  have it set, showing the message only.
- Animations must be cheap on low-end Android devices. Cap particle counts,
  use transform/opacity only, and clean up timers on unmount.
- Do not fire the payment animation on page refresh — only on first load
  after payment.

────────────────────────────────
General
────────────────────────────────
- Test everything at 375px width with real touch events.
- Do not change the queue worker configuration or the Telegram notifier.
- Existing tests must still pass. Add tests for: correct redirect after
  payment, receipt only accessible for paid orders, waiter cannot edit an
  order but can view its receipt.
- List every file changed.