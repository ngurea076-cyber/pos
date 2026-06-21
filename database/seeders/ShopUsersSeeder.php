<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopUsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            User::updateOrCreate(
                ['email' => 'shop@ict.co.ke'],
                ['name' => 'Shop Attendant', 'role' => 'attendant', 'password' => 'Shop@2026']
            );

            User::updateOrCreate(
                ['email' => 'admin@ict.co.ke'],
                ['name' => 'ShopICT Admin', 'role' => 'admin', 'password' => 'Admin@2026']
            );

            User::whereNotIn('email', ['shop@ict.co.ke', 'admin@ict.co.ke'])->delete();
        });
    }
}
