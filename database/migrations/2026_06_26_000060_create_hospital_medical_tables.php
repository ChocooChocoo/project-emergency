<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Hospitals, endorsements, handoff summaries, patients, vitals, treatments, notes. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->index();
            $table->string('name', 150);
            $table->string('facility_type', 50)->default('hospital');
            $table->string('ownership', 50)->nullable();
            $table->string('ambulance_status', 80)->default('needs_direct_verification');
            $table->string('city', 100)->default('Dasmariñas');
            $table->string('province', 100)->default('Cavite');
            $table->text('hotlines_json')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('capacity_status', 50)->default('unknown');
            $table->integer('available_beds')->nullable();
            $table->boolean('is_er_open')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_archived')->default(false);
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->index();
            $table->text('archive_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('hospital_endorsements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_assignment_id')->nullable()->index();
            $table->foreignId('incident_id')->nullable()->index();
            $table->foreignId('hospital_id')->index();
            $table->foreignId('endorsed_by')->nullable()->index();
            $table->foreignId('received_by')->nullable()->index();
            $table->string('status', 50)->default('pending');
            $table->foreignId('responded_by')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->text('response_notes')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('handoff_confirmed_at')->nullable();
            $table->text('outcome_note')->nullable();
            $table->string('handoff_status', 50)->default('pending');
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('handoff_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->unique();
            $table->text('summary');
            $table->string('outcome', 150)->nullable();
            $table->string('handoff_to', 150)->nullable();
            $table->dateTime('handoff_at')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->unique();
            $table->string('full_name', 150)->nullable();
            $table->string('sex', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('vitals_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id');
            $table->dateTime('recorded_at')->useCurrent();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->unsignedSmallInteger('oxygen_saturation')->nullable();
            $table->decimal('blood_glucose', 6, 2)->nullable();
            $table->unsignedTinyInteger('gcs_score')->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->index(['incident_id', 'recorded_at']);
        });

        Schema::create('treatment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id');
            $table->dateTime('performed_at')->useCurrent();
            $table->string('treatment_type', 150);
            $table->text('details')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->index(['incident_id', 'performed_at']);
        });

        Schema::create('prehospital_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id');
            $table->string('note_type', 100)->default('general');
            $table->text('content');
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach ([
            'prehospital_notes', 'treatment_records', 'vitals_entries', 'patients',
            'handoff_summaries', 'hospital_endorsements', 'hospitals',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
