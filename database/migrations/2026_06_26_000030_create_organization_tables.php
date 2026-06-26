<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plans, organizations, subscriptions, payments, coverage, documents, geo layers. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one_time', 'custom'])->default('monthly');
            $table->integer('max_dispatchers')->nullable();
            $table->integer('max_drivers')->nullable();
            $table->integer('max_ambulances')->nullable();
            $table->integer('max_hospitals')->nullable();
            $table->integer('max_members')->nullable();
            $table->integer('max_roles_assignable')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // R11: slimmed — plan limits live in `plans`; subscription state in `organization_subscriptions`.
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('org_type', 50)->default('lgu');
            $table->string('name', 150);
            $table->string('org_acronym', 80)->nullable();
            $table->string('registration_permit_number', 120)->nullable();
            $table->string('code', 50)->nullable()->unique();
            $table->string('email', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('dispatch_hotline_ops', 50)->nullable();
            $table->text('base_station_location')->nullable();
            $table->text('hospital_partners')->nullable();
            $table->text('onboarding_reviewer_notes')->nullable();
            $table->integer('ambulance_count')->nullable();
            $table->string('service_type', 100)->nullable();
            $table->boolean('is_24_7')->default(false);
            $table->string('admin_contact_title', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('service_city', 100)->default('Dasmariñas');
            $table->text('covered_barangays_json')->nullable();
            $table->string('org_zone_type', 150)->nullable();
            $table->string('coverage_jurisdiction', 40)->nullable();
            $table->decimal('service_radius_km', 6, 2)->nullable();
            $table->decimal('hq_latitude', 10, 7)->nullable();
            $table->decimal('hq_longitude', 10, 7)->nullable();
            $table->text('coverage_summary')->nullable();
            $table->string('registration_type', 50)->nullable();
            $table->string('organization_identity', 40)->nullable();
            $table->string('organization_status', 50)->default('pending_review')->index();
            $table->string('registration_status', 50)->nullable()->index();
            $table->foreignId('admin_user_id')->nullable()->index();
            $table->string('aor_mode', 30)->default('exclusive');
            $table->integer('auto_progression_radius_m')->default(50);
            $table->unsignedInteger('response_target_minutes')->default(8);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->dateTime('org_profile_completed_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->index();
            $table->text('archive_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->unique();
            $table->foreignId('plan_id')->index();
            $table->enum('status', ['trialing', 'active', 'past_due', 'cancelled', 'paused'])->default('trialing');
            $table->dateTime('payment_confirmed_at')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->index();
            $table->foreignId('plan_id')->nullable()->index();
            $table->string('provider', 50)->default('mock');
            $table->string('provider_reference')->nullable();
            $table->string('provider_event_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('PHP');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_coverage_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->index();
            $table->string('coverage_name', 150)->nullable();
            $table->string('area_name', 150)->nullable();
            $table->string('barangay_name', 100)->nullable();
            $table->json('polygon_json')->nullable();
            $table->json('coordinates_json')->nullable();
            $table->integer('priority_rank')->default(1);
            $table->boolean('is_overlap_allowed')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('organization_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->index();
            $table->string('document_type', 100)->index();
            $table->string('document_number', 100)->nullable();
            $table->string('file_path')->nullable();
            $table->string('validation_status', 50)->default('pending')->index();
            $table->boolean('is_optional')->default(false);
            $table->foreignId('validated_by')->nullable()->index();
            $table->dateTime('validated_at')->nullable();
            $table->dateTime('submitted_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('geo_layers', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('label', 200)->default('');
            $table->longText('geojson');
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'geo_layers', 'organization_documents', 'organization_coverage_areas',
            'subscription_payments', 'organization_subscriptions', 'organizations', 'plans',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
