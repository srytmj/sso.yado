<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::where('slug', 'user')->firstOrFail();

        User::firstOrCreate(
            ['email' => 'user@yado.my.id'],
            [
                'name' => 'Test User',
                'username' => 'testuser',
                'password' => 'user',
                'role_id' => $userRole->id,
                'is_active' => true,
            ]
        );
    }
}
