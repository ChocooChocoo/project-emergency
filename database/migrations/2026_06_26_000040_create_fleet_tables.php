<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ambulances + locations, status logs, fuel, maintenance, readiness, duty. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambulances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->string('plate_no', 50);
            $table->string('unit_code', 50)->nullable();
            $table->string('vehicle_name', 100)->nullable();
            $table->string('vehicle_type', 100)->nullable();
            $table->enum('tier', ['bls', 'als'])->nullable()->index();   // R3
            $table->string('doh_credential_ref', 100)->nullable();       // R3
            $table->boolean('has_ventilator')->default(false);           // R3 equipment flags
            $table->boolean('has_oxygen')->default(false);
            $table->boolean('has_aed')->default(false);
            $table->boolean('has_spine_board')->default(false);
            $table->boolean('has_ob_kit')->default(false);
            $table->boolean('has_stretcher')->default(false);
            $table->integer('capacity_patients')->default(1);
            $table->text('equipment_notes')->nullable();
            $table->foreignId('current_driver_user_id')->nullable()->index();
            $table->string('readiness_status', 50)->default('ready');
            $table->string('status', 50)->default('available')->index();
            $table->boolean('is_serviceable')->default(true);
            $table->decimal('current_odometer_km', 10, 2)->default(0);
            $table->date('next_maintenance_date')->nullable();
            $table->decimal('last_lat', 10, 8)->nullable();
            $table->decimal('last_lng', 11, 8)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->index();
            $table->text('archive_reason')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'plate_no']);
        });

        Schema::create('ambulance_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id');
            $table->foreignId('dispatch_assignment_id')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->dateTime('recorded_at');
            $table->index(['ambulance_id', 'recorded_at'], 'idx_amb_loc_ambulance_time');
            $table->index(['dispatch_assignment_id', 'recorded_at'], 'idx_amb_loc_assignment_time');
        });

        Schema::create('ambulance_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id')->index();
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->foreignId('changed_by')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id');
            $table->date('log_date');
            $table->decimal('liters', 10, 2);
            $table->decimal('cost_per_liter', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->integer('odometer_km')->nullable();
            $table->enum('fuel_type', ['diesel', 'gasoline', 'premium', 'other'])->default('diesel');
            $table->string('station', 150)->nullable();
            $table->string('filled_by', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();
            $table->index(['ambulance_id', 'log_date']);
        });

        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id')->index();
            $table->enum('maintenance_type', [
                'preventive', 'corrective', 'emergency', 'inspection',
                'tire', 'oil_change', 'brake', 'battery', 'other',
            ])->default('preventive');
            $table->text('description');
            $table->string('performed_by', 150)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('odometer_km')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->date('performed_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->integer('next_due_km')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled')->index();
            $table->text('parts_replaced')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('updated_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('unit_readiness_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id');
            $table->foreignId('driver_user_id')->index();
            $table->date('check_date');
            $table->json('checks');
            $table->boolean('is_all_passed')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['ambulance_id', 'driver_user_id', 'check_date'], 'uq_unit_readiness_daily');
        });

        Schema::create('driver_duty_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_user_id')->unique();
            $table->foreignId('ambulance_id')->nullable()->index();
            $table->enum('status', ['on_duty', 'off_duty', 'break'])->default('off_duty');
            $table->dateTime('started_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'driver_duty_states', 'unit_readiness_checks', 'maintenance_logs',
            'fuel_logs', 'ambulance_status_logs', 'ambulance_locations', 'ambulances',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
