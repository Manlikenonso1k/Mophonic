<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Super admin holds every permission through the Gate::before check in
        // AppServiceProvider, so it is deliberately not synced here — new
        // permissions never need re-granting.
        Role::findOrCreate(User::SUPER_ADMIN, 'web');

        Role::findOrCreate(User::WAITER, 'web')
            ->syncPermissions(Permissions::waiter());

        $this->assignSuperAdmin();
    }

    protected function assignSuperAdmin(): void
    {
        $email = config('admin.super_admin_email') ?: config('admin.fallback_admin_email');

        if (blank($email)) {
            $this->warn('ADMIN_SUPER_EMAIL is not set.');
        } elseif ($user = User::query()->where('email', $email)->first()) {
            $user->assignRole(User::SUPER_ADMIN);

            $this->command?->info("Granted super_admin to {$email}.");

            return;
        } else {
            $this->warn("No user with the email {$email}.");
        }

        $this->rescueFromLockout();
    }

    /**
     * Panel access needs a role, so a deployment that seeds without a usable
     * ADMIN_SUPER_EMAIL would leave nobody able to log in. When no super admin
     * exists at all, the oldest account is promoted rather than locking the
     * owner out of their own site.
     */
    protected function rescueFromLockout(): void
    {
        $superAdmins = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', User::SUPER_ADMIN))
            ->count();

        if ($superAdmins > 0) {
            $this->command?->info('An existing super admin was left in place.');

            return;
        }

        $user = User::query()->oldest('id')->first();

        if (! $user) {
            $this->warn('There are no accounts yet — create one, then run this seeder again.');

            return;
        }

        $user->assignRole(User::SUPER_ADMIN);

        $this->warn("No super admin existed, so the first account ({$user->email}) was promoted.");
    }

    protected function warn(string $message): void
    {
        Log::warning($message);

        $this->command?->warn($message);
    }
}
