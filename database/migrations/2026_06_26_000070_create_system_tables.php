<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Approvals, audit/archival/system logs, configuration, notifications, ads. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->enum('target_type', ['user', 'organization', 'completion_report', 'document', 'other']);
            $table->unsignedBigInteger('target_id');
            $table->string('request_type', 100);
            $table->enum('status', ['pending', 'approved', 'rejected', 'returned'])->default('pending')->index();
            $table->foreignId('organization_id')->nullable()->index();
            $table->foreignId('requested_by')->nullable()->index();
            $table->foreignId('reviewed_by')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('payload_json')->nullable();
            $table->dateTime('requested_at')->useCurrent();
            $table->dateTime('reviewed_at')->nullable();
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('role', 50)->nullable();
            $table->foreignId('organization_id')->nullable()->index();
            $table->string('action', 150);
            $table->string('model_type', 100)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->longText('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
        });

        Schema::create('archival_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id');
            $table->foreignId('archived_by')->nullable()->index();
            $table->text('archive_reason')->nullable();
            $table->dateTime('archived_at')->useCurrent();
            $table->longText('snapshot_json')->nullable();
            $table->index(['table_name', 'record_id']);
        });

        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20);
            $table->string('category', 50)->nullable();
            $table->text('message');
            $table->longText('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['level', 'created_at']);
        });

        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['global', 'organization'])->default('global');
            $table->foreignId('organization_id')->nullable()->index();
            $table->string('config_key', 100);
            $table->text('config_value');
            $table->enum('config_type', ['string', 'int', 'float', 'boolean', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['scope', 'organization_id', 'config_key'], 'uq_sys_config_scope_key');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->default(1)->primary();
            $table->json('settings_json');
            $table->foreignId('updated_by')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('type', 100)->default('system');
            $table->string('title', 150);
            $table->text('message');
            $table->json('data_json')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'is_read']);
        });

        Schema::create('ad_placements', function (Blueprint $table) {     // R9
            $table->id();
            $table->string('slot', 100);
            $table->string('title', 150)->nullable();
            $table->text('content')->nullable();
            $table->string('target_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_emergency_safe')->default(false);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
            $table->index(['slot', 'is_active']);
        });
    }

    public function down(): void
    {
        foreach ([
            'ad_placements', 'notifications', 'system_settings', 'system_configurations',
            'system_logs', 'archival_logs', 'audit_logs', 'approval_records',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
