<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/** Incidents, updates, dispatch assignments, completion reports, outcome logs. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('request_code', 50)->unique();
            $table->enum('request_type', ['one_tap', 'detailed', 'non_emergency', 'scheduled'])->default('one_tap')->index(); // R8
            $table->dateTime('scheduled_for')->nullable();               // R8
            $table->foreignId('master_incident_id')->nullable()->index(); // R2 (self-ref)
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('guest_id')->nullable()->index();
            $table->foreignId('organization_id')->nullable()->index();
            $table->foreignId('coverage_area_id')->nullable()->index();
            $table->string('patient_name', 150)->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('incident_type', 100)->nullable();            // was `type`
            $table->string('priority_label', 30)->nullable();
            $table->unsignedTinyInteger('severity')->default(4);
            $table->unsignedSmallInteger('patient_count')->nullable();
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->text('pickup_address');
            $table->string('pickup_landmark')->nullable();
            $table->foreignId('destination_hospital_id')->nullable()->index();
            $table->enum('status', [
                'pending', 'dispatched', 'ongoing', 'on_scene', 'transporting',
                'arrived_at_hospital', 'completed', 'cancelled', 'resolved_on_scene',
            ])->default('pending')->index();
            $table->string('dispatch_routing_state', 64)->nullable();
            $table->longText('dss_org_queue_json')->nullable();
            $table->string('outcome_tag', 50)->default('none');
            $table->boolean('is_flagged_for_abuse')->default(false);
            $table->boolean('is_public_tracking')->default(true);
            $table->integer('eta_minutes')->nullable();
            $table->text('request_summary')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('arrived_scene_at')->nullable();
            $table->dateTime('resolved_on_scene_at')->nullable();
            $table->dateTime('transport_started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->index();
            $table->text('archive_reason')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        // R6: a request comes from a registered user OR a guest, never both.
        // NOTE: enforced in the application layer (model observer), not a DB CHECK —
        // MySQL 8 forbids a column in both a CHECK and a SET NULL foreign key (err 3823).

        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id');
            $table->foreignId('dispatch_assignment_id')->nullable()->index();
            $table->string('status', 50);
            $table->string('care_status', 50)->nullable();
            $table->string('update_type', 32)->nullable();
            $table->text('note')->nullable();
            $table->enum('visibility', ['private', 'organization', 'public'])->default('organization');
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['incident_id', 'created_at']);
        });

        Schema::create('dispatch_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->index();
            $table->foreignId('organization_id')->index();
            $table->foreignId('dispatcher_user_id')->nullable()->index();
            $table->foreignId('assigned_by')->nullable()->index();
            $table->foreignId('ambulance_id')->index();
            $table->foreignId('driver_user_id')->index();
            $table->foreignId('hospital_id')->nullable()->index();
            $table->enum('status', [
                'assigned', 'accepted', 'acknowledged', 'en_route', 'approaching_scene',
                'arrived_on_scene', 'departing_scene', 'approaching_hospital', 'arrived_hospital',
                'clear_for_duty', 'timed_out', 'rejected', 'reassigned', 'cancelled',
                'completed', 'unassigned',
            ])->default('assigned')->index();
            $table->string('care_status', 50)->default('awaiting_assessment')->index();
            $table->integer('scene_approach_radius_m')->default(1000);
            $table->integer('scene_arrival_radius_m')->default(200);
            $table->integer('facility_approach_radius_m')->default(1000);
            $table->integer('facility_arrival_radius_m')->default(200);
            $table->dateTime('assigned_at')->useCurrent();
            $table->dateTime('response_deadline_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('en_route_at')->nullable();
            $table->dateTime('approaching_scene_at')->nullable();
            $table->dateTime('arrived_on_scene_at')->nullable();         // R10 (deduped)
            $table->dateTime('departed_scene_at')->nullable();
            $table->dateTime('transport_started_at')->nullable();
            $table->dateTime('near_destination_at')->nullable();
            $table->dateTime('arrived_at_hospital_at')->nullable();      // R10 (deduped)
            $table->dateTime('handover_completed_at')->nullable();
            $table->dateTime('timed_out_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->foreignId('forwarded_from_assignment_id')->nullable()->index();
            $table->integer('alert_attempts')->default(0);
            $table->integer('dss_rank')->nullable();
            $table->text('dispatch_notes')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->dateTime('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->index();
            $table->text('archive_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('driver_completion_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->index();
            $table->foreignId('incident_id')->index();
            $table->foreignId('ambulance_id')->index();
            $table->foreignId('driver_user_id')->index();
            $table->text('scene_summary')->nullable();
            $table->text('first_aid_summary')->nullable();
            $table->text('transport_summary')->nullable();
            $table->text('handover_summary')->nullable();
            $table->text('outcome_notes')->nullable();
            $table->enum('patient_status', [
                'stable', 'critical', 'deceased', 'refused_transport', 'resolved_on_scene', 'other',
            ])->nullable();
            $table->decimal('odometer_end', 10, 2)->nullable();
            $table->string('review_status', 30)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->index();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('extra_notes')->nullable();
            $table->dateTime('submitted_at')->useCurrent();
        });

        Schema::create('request_outcome_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->index();
            $table->string('tag', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('severity', 20)->default('medium');
            $table->string('action_taken', 150)->nullable();
            $table->string('outcome_tag', 50)->nullable();
            $table->integer('offense_level')->default(0);
            $table->text('internal_notes')->nullable();
            $table->foreignId('logged_by_id')->nullable()->index();
            $table->foreignId('tagged_by_user_id')->nullable()->index();
            $table->dateTime('tagged_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach ([
            'request_outcome_logs', 'driver_completion_reports',
            'dispatch_assignments', 'incident_updates', 'incidents',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
