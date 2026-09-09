<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

/**
 * The way back in when nobody can reach the panel — a locked-out super admin,
 * a fresh deployment, or a role removed by mistake.
 */
class AssignRole extends Command
{
    protected $signature = 'role:assign
                            {email : The account to grant the role to}
                            {role : The role name, e.g. super_admin or waiter}
                            {--replace : Remove the account\'s other roles first}';

    protected $description = 'Assign a role to a user by email';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $roleName = (string) $this->argument('role');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("No user with the email {$email}.");

            return self::FAILURE;
        }

        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

        if (! $role) {
            $available = Role::query()->pluck('name')->implode(', ');

            $this->error("No role named {$roleName}. Available: ".($available ?: 'none — run the seeder first'));

            return self::FAILURE;
        }

        if ($this->option('replace')) {
            $user->syncRoles([$role->name]);
        } else {
            $user->assignRole($role->name);
        }

        $this->info("{$email} now has: ".$user->fresh()->roles->pluck('name')->implode(', '));

        return self::SUCCESS;
    }
}
