<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Citizen portal — standing medical info (blood_type, allergies, conditions, notes).
 * ponytail: one JSON column on users; no separate table until the fields grow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('medical_info')->nullable()->after('alt_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('medical_info');
        });
    }
};
