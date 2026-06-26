<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rescue Platform — core users table (+ framework session/reset tables).
 * Foreign keys are added later in the dedicated add_foreign_keys migration
 * (avoids ordering/circular-dependency issues).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->enum('account_type', [
                'citizen', 'minor', 'personnel', 'org_admin',
                'dispatcher', 'driver', 'hospital_staff', 'super_admin',
            ])->default('citizen')->index();
            $table->foreignId('organization_id')->nullable()->index();
            $table->foreignId('hospital_id')->nullable()->index();
            $table->string('requested_role', 50)->default('user');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 20)->nullable();
            $table->string('email', 150)->unique();
            $table->string('phone', 50)->nullable();
            $table->string('alt_phone', 50)->nullable();
            $table->string('password');                                  // R13 (was password_hash)
            $table->string('profile_image')->nullable();
            $table->string('account_status', 50)->default('pending_otp')->index();
            $table->string('id_validation_status', 50)->default('not_submitted');
            $table->string('guardian_id_validation_status', 50)->default('not_required');
            $table->dateTime('terms_accepted_at')->nullable();
            $table->string('terms_version', 50)->nullable();
            $table->string('document_type', 50)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->dateTime('email_verified_at')->nullable();
            $table->dateTime('profile_completed_at')->nullable();
            $table->string('registration_source', 50)->default('direct');
            $table->text('rejected_reason')->nullable();
            $table->integer('rejection_count')->default(0);
            $table->text('suspension_reason')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->foreignId('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->index();
            $table->text('archive_reason')->nullable();
            $table->timestamps();
        });

        // Framework tables (kept for cache/queue/session drivers).
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
