<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Identity & access: roles, permissions, sessions, verification, anti-abuse. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {            // R1: org-owned
            $table->id();
            $table->foreignId('organization_id')->nullable();           // NULL = platform/global role
            $table->string('name', 100);
            $table->enum('scope', ['platform', 'organization', 'citizen']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'name']);                 // R1
            $table->index('scope');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 150)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('module', 100);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('permission_id')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('role_id')->index();
            $table->foreignId('organization_id')->nullable()->index();
            $table->foreignId('assigned_by')->nullable()->index();
            $table->dateTime('assigned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'role_id', 'organization_id'], 'uq_user_roles_scope');
        });

        Schema::create('user_permissions', function (Blueprint $table) { // R5: FK, not loose string
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('organization_id')->index();
            $table->foreignId('permission_id')->index();
            $table->foreignId('granted_by')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'organization_id', 'permission_id'], 'uq_user_perms_scope');
        });

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('token_hash')->unique();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('user_google_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->string('google_sub')->unique();
            $table->string('email_at_link')->nullable();
            $table->timestamps();
        });

        Schema::create('email_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('code_hash');
            $table->integer('attempt_count')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('sent_at')->useCurrent();
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('code_hash');
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('terms_acceptance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('terms_version', 50);
            $table->dateTime('accepted_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
        });

        Schema::create('device_tokens', function (Blueprint $table) {    // R4
            $table->id();
            $table->string('device_uuid', 128)->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->integer('false_alarm_count')->default(0);
            $table->dateTime('last_flagged_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->dateTime('blocked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('account_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->enum('flag_type', [
                'misuse', 'false_alarm', 'identity_issue',
                'duplicate_account', 'security_review', 'manual_review',
            ]);
            $table->text('reason');
            $table->string('source_model', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->dateTime('active_from')->useCurrent();
            $table->dateTime('active_until')->nullable();
            $table->boolean('is_resolved')->default(false)->index();
            $table->foreignId('resolved_by')->nullable()->index();
            $table->dateTime('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach ([
            'account_flags', 'device_tokens', 'terms_acceptance_logs',
            'password_reset_codes', 'email_verification_codes', 'user_google_identities',
            'user_sessions', 'user_permissions', 'user_roles', 'role_permissions',
            'permissions', 'roles',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
