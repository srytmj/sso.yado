<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_id', 26)->nullable()->after('id');
        });

        DB::table('users')->select('id')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'account_id' => (string) Str::ulid(),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('account_id', 26)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
