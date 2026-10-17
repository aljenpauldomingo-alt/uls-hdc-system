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
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill existing users with a username based on their email
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $baseUsername = Str::before($user->email, '@');
            $username = $baseUsername;
            $counter = 1;

            // Ensure uniqueness
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $baseUsername.$counter;
                $counter++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }

        // Make the column required after backfilling
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
