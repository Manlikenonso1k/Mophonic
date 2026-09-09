Add role-based access control to the Filament admin panel.

Package: spatie/laravel-permission. Install, publish and run its migrations.
Add HasRoles to the User model.

Roles and permissions:
- Create a seeder defining two roles: super_admin and waiter.
- super_admin: all permissions, including managing users and assigning roles.
- waiter: can view orders and order details, and can create/update/delete
  product and work images. Cannot view users, cannot change prices, cannot
  delete orders, cannot access settings.
- Define permissions granularly (e.g. view_order, view_any_order,
  update_product_image, ...) rather than one blanket permission per resource.

Super admin bootstrapping:
- Add config/admin.php with a 'super_admin_email' value read from
  ADMIN_SUPER_EMAIL in .env. Do not hardcode the address in code.
- Seeder assigns super_admin to that email if the user exists; if not,
  log a warning rather than failing.
- Add a Gate::before check granting super_admin every permission, so new
  permissions never need re-granting.

Filament:
- Add a UserResource, visible only to super_admin, that can create users
  and assign roles. Never allow a user to change their own role, and never
  allow the last super_admin to be demoted or deleted.
- Guard every existing Resource with canViewAny/canCreate/canUpdate/canDelete
  based on permissions, not on role names.
- Hide navigation items the user lacks permission for, so the waiter sees
  a clean sidebar rather than pages that error on click.
- On the Order resource for waiters: make it read-only — view details, no
  edit form, no delete action, no bulk actions.

Also:
- An artisan command to assign a role to a user by email, for recovery.
- Tests: a waiter cannot reach the user list, cannot edit an order, and
  can update a product image; a super_admin can do all three; the last
  super_admin cannot be demoted.