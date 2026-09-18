<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Role 'admin' tidak digunakan - hanya 'user' dan 'superadmin'
        // User yang punya role admin di-reassign ke 'user' sebelum dihapus
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();

        if ($adminRole) {
            $userRole = DB::table('roles')->where('slug', 'user')->value('id');

            if ($userRole) {
                DB::table('users')
                    ->where('role_id', $adminRole->id)
                    ->update(['role_id' => $userRole]);
            }

            DB::table('roles')->where('slug', 'admin')->delete();
        }
    }

    public function down(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['name' => 'Admin', 'slug' => 'admin', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
};
