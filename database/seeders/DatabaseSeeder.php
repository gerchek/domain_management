<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // Create Buyer
        User::firstOrCreate(
            ['email' => 'buyer@buyer.com'],
            [
                'name' => 'Buyer',
                'password' => Hash::make('password'),
                'role' => 'buyer',
                'is_active' => true,
            ]
        );

        // Create default settings
        Setting::set('dynadot_api_key', '');
        Setting::set('domains_per_request', 20);
    }
}
