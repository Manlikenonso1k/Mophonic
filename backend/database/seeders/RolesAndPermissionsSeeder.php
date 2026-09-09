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
        $email = config('admin.super_admin_email');

        if (blank($email)) {
            $this->warn('ADMIN_SUPER_EMAIL is not set — no super admin was assigned.');

            return;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->warn("No user with the email {$email} — super admin was not assigned.");

            return;
        }

        $user->assignRole(User::SUPER_ADMIN);

        $this->command?->info("Granted super_admin to {$email}.");
    }

    protected function warn(string $message): void
    {
        Log::warning($message);

        $this->command?->warn($message);
    }
}
