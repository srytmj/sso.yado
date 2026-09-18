<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'superadmin')->firstOrFail();

        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@yado.my.id')],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => env('ADMIN_PASSWORD', 'admin'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
