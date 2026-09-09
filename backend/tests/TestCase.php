<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * An account that can reach every admin screen. Panel access now depends on
     * roles, so tests that drive the admin need one rather than a bare user.
     */
    protected function superAdminUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create()->assignRole(User::SUPER_ADMIN);
    }
}
