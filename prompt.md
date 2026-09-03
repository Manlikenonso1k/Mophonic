Two things on the Filament admin panel (Laravel 13 + Filament 4, Mophonik).
Read FILAMENT_UI.md first — it documents how to customize Filament in this
project. Follow it rather than discovering the theming by trial and error.

--- TASK 1: USER MANAGEMENT ---

I need to create and manage admin users from inside the panel instead of running
`php artisan make:filament-user` every time.

Build a Users resource:
- List: name, email, created date, with search.
- Create: name, email, password + confirmation. Hash the password on save —
  never write plaintext to the column.
- Edit: same fields, but leave the password blank to keep the existing one.
  Only hash and update when a new value is entered.
- Delete: block a user from deleting their own account, or the last remaining
  admin. Getting locked out of the panel is the failure mode here.
- Validate email uniqueness and require a reasonable minimum password length.

Style it to match the existing resources in the panel, per FILAMENT_UI.md — not
default Filament styling.

Access control: check how canAccessPanel is currently implemented on the User
model. If every user can reach the panel, tell me before you build this — a
Users screen that anyone can reach means anyone can mint themselves an admin.
Show me the current state and your proposed approach before writing it.

Write tests: a user can be created and the password is hashed; editing with a
blank password leaves the hash unchanged; a user cannot delete themselves.

--- TASK 2: MOBILE SIDEBAR AT 320px ---

At 320px viewport, opening the hamburger menu makes the sidebar cover the entire
screen with no visible way back — there's no backdrop to tap and no close control.

Fix it so the menu is dismissable:
- The sidebar should not occupy the full viewport width. Leave a visible strip of
  the page behind it.
- Add a dimmed backdrop over the remaining area that closes the menu when tapped.
- Add an explicit close (X) control in the sidebar header, since a backdrop alone
  isn't obvious on a small screen.
- Escape key closes it too.
- When open, prevent the page behind from scrolling.

Check FILAMENT_UI.md for how this panel's theme is built before overriding
anything. Prefer Filament's own configuration and theme CSS over blanket
!important overrides on its internal classes — those break on the next upgrade.

Verify at 320px, 375px, and 768px. Confirm the desktop sidebar behaviour is
unchanged.