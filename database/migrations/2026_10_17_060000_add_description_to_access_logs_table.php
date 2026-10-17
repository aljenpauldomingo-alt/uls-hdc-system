<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Access logs now record any staff/director action (records, SATA, appointments,
 * counselor/college/program changes, logins) with a human-readable description.
 * Non-record actions leave both record FKs null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('action');
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
