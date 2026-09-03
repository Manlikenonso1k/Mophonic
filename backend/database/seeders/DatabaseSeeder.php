<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@mophonik.test')],
            [
                'name' => 'Mophonik Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ],
        );

        $this->call([
            WorkSeeder::class,
            ShopSeeder::class,
        ]);
    }
}
