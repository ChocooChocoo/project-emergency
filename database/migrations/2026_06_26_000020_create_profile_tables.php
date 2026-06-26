<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Citizen profile, medical history, identity documents, guardians, guests. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->date('birth_date')->nullable();
            $table->boolean('is_minor')->default(false);
            $table->string('sex', 20)->nullable();
            $table->text('address_line')->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('city', 100)->default('Dasmarinas');
            $table->string('province', 100)->default('Cavite');
            $table->string('birth_place')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('license_no', 100)->nullable();
            $table->string('hospital_name', 150)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position_title', 100)->nullable();
            $table->string('id_type', 100)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->string('id_image_path')->nullable();
            $table->longText('encrypted_sensitive_json')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('blood_type', 10)->nullable();
            $table->text('allergies')->nullable();
            $table->text('chronic_conditions')->nullable();
            $table->text('medications')->nullable();
            $table->text('surgeries')->nullable();
            $table->text('notes')->nullable();
            $table->longText('encrypted_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('user_identity_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('document_type', 50);
            $table->string('document_number', 100)->nullable();
            $table->string('file_path');
            $table->char('document_hash', 64)->nullable();
            $table->string('validation_provider', 100)->nullable();
            $table->string('validation_status', 50)->default('pending')->index();
            $table->json('validation_details_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('submitted_at')->useCurrent();
            $table->dateTime('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->index();
            $table->dateTime('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guardian_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minor_user_id')->unique();
            $table->foreignId('guardian_user_id')->nullable()->index();
            $table->string('guardian_name');
            $table->string('guardian_relationship', 100);
            $table->string('guardian_contact_number', 50);
            $table->string('guardian_email', 150)->nullable();
            $table->foreignId('guardian_document_id')->nullable()->index();
            $table->boolean('is_verified')->default(false);
            $table->string('verified_status', 50)->default('pending');
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->index();
            $table->dateTime('declared_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('guest_key', 64)->unique();
            $table->string('phone', 50)->nullable();
            $table->string('ip_first_seen', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('requests_limit')->default(2);
            $table->unsignedInteger('requests_used')->default(0);
            $table->foreignId('upgraded_user_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('disabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'guest_sessions', 'guardian_links', 'user_identity_documents',
            'user_medical_histories', 'citizen_profiles',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
