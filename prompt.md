Bug: checkout is completely broken on mobile, works fine on desktop.

Symptom: on a mobile browser, opening the cart and tapping checkout causes
cart items to be removed one at a time until the cart is empty, and no
checkout or payment redirect happens. Desktop is unaffected.

Investigate before changing anything. Report what you find, then fix.

Prime suspects, check each and tell me which applies:
1. Ghost/double-fire touch events — a handler bound to both onClick and
   onTouchStart/onTouchEnd, firing twice per tap.
2. Event bubbling — the remove-item button is inside the checkout button's
   click target, or a parent onClick catches taps that miss a child on a
   narrow viewport.
3. Overlapping hit areas at mobile breakpoints — the remove control sits
   under the checkout button once the layout stacks. Check computed
   positions at 375px width, not just whether it looks right.
4. A click handler firing on a re-render loop, where removing an item
   re-renders and immediately triggers the next removal.
5. Cart persistence failing on mobile — localStorage/sessionStorage blocked
   in private browsing on iOS Safari, so state resets on each interaction.
6. The checkout request failing silently (CORS, mixed content, or an
   unhandled promise rejection) with no error surfaced to the user.

Required fixes regardless of cause:
- Every remove-item control must call stopPropagation and preventDefault.
- Never bind both a click and a touch handler to the same action.
- Minimum 44x44px touch targets on all cart controls, with adequate spacing
  so adjacent controls cannot be hit accidentally.
- The checkout action must surface errors to the user — a failed request
  should show a message, never fail silently.
- Add a guard so checkout cannot fire while a request is already in flight.

Verification:
- Test at 375px and 414px viewport widths, not just desktop with a narrow
  window — touch events differ from mouse events and a resized desktop
  browser will not reproduce this.
- Confirm the full flow: add to cart, open cart, checkout, Paystack redirect.
- Add a regression test that a tap on checkout does not trigger remove-item.

Report the root cause explicitly before showing the fix.