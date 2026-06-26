<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All foreign keys, added after every table exists (order-independent, no
 * circular-dependency issues). Table names are unprefixed — the connection's
 * 'tbl_' prefix is applied automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $t->foreign('hospital_id')->references('id')->on('hospitals')->nullOnDelete();
            $t->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('roles', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });

        Schema::table('role_permissions', function (Blueprint $t) {
            $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::table('user_roles', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('user_permissions', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $t->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('user_sessions', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('user_google_identities', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('email_verification_codes', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('password_reset_codes', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('terms_acceptance_logs', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('device_tokens', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->nullOnDelete());

        Schema::table('account_flags', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('citizen_profiles', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('user_medical_histories', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());

        Schema::table('user_identity_documents', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('guardian_links', function (Blueprint $t) {
            $t->foreign('minor_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('guardian_user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('guardian_document_id')->references('id')->on('user_identity_documents')->nullOnDelete();
            $t->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('guest_sessions', fn (Blueprint $t) => $t->foreign('upgraded_user_id')->references('id')->on('users')->nullOnDelete());

        Schema::table('organizations', function (Blueprint $t) {
            $t->foreign('admin_user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('organization_subscriptions', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('plan_id')->references('id')->on('plans');
        });

        Schema::table('subscription_payments', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });

        Schema::table('organization_coverage_areas', fn (Blueprint $t) => $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete());

        Schema::table('organization_documents', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('ambulances', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations');
            $t->foreign('current_driver_user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('ambulance_locations', function (Blueprint $t) {
            $t->foreign('ambulance_id')->references('id')->on('ambulances')->cascadeOnDelete();
            $t->foreign('dispatch_assignment_id')->references('id')->on('dispatch_assignments')->nullOnDelete();
        });

        Schema::table('ambulance_status_logs', function (Blueprint $t) {
            $t->foreign('ambulance_id')->references('id')->on('ambulances')->cascadeOnDelete();
            $t->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('fuel_logs', function (Blueprint $t) {
            $t->foreign('ambulance_id')->references('id')->on('ambulances')->cascadeOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('maintenance_logs', function (Blueprint $t) {
            $t->foreign('ambulance_id')->references('id')->on('ambulances')->cascadeOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('unit_readiness_checks', function (Blueprint $t) {
            $t->foreign('ambulance_id')->references('id')->on('ambulances')->cascadeOnDelete();
            $t->foreign('driver_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('driver_duty_states', function (Blueprint $t) {
            $t->foreign('driver_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('ambulance_id')->references('id')->on('ambulances')->nullOnDelete();
        });

        Schema::table('incidents', function (Blueprint $t) {
            $t->foreign('master_incident_id')->references('id')->on('incidents')->nullOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('guest_id')->references('id')->on('guest_sessions')->nullOnDelete();
            $t->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $t->foreign('coverage_area_id')->references('id')->on('organization_coverage_areas')->nullOnDelete();
            $t->foreign('destination_hospital_id')->references('id')->on('hospitals')->nullOnDelete();
            $t->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('incident_updates', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('dispatch_assignment_id')->references('id')->on('dispatch_assignments')->nullOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('dispatch_assignments', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('organization_id')->references('id')->on('organizations');
            $t->foreign('ambulance_id')->references('id')->on('ambulances');
            $t->foreign('driver_user_id')->references('id')->on('users');
            $t->foreign('dispatcher_user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('hospital_id')->references('id')->on('hospitals')->nullOnDelete();
            $t->foreign('forwarded_from_assignment_id')->references('id')->on('dispatch_assignments')->nullOnDelete();
            $t->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('driver_completion_reports', function (Blueprint $t) {
            $t->foreign('assignment_id')->references('id')->on('dispatch_assignments')->cascadeOnDelete();
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('ambulance_id')->references('id')->on('ambulances')->cascadeOnDelete();
            $t->foreign('driver_user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('request_outcome_logs', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('tagged_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('hospitals', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('archived_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('hospital_endorsements', function (Blueprint $t) {
            $t->foreign('dispatch_assignment_id')->references('id')->on('dispatch_assignments')->cascadeOnDelete();
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('hospital_id')->references('id')->on('hospitals')->cascadeOnDelete();
            $t->foreign('endorsed_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('handoff_summaries', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('patients', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('vitals_entries', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('treatment_records', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('prehospital_notes', function (Blueprint $t) {
            $t->foreign('incident_id')->references('id')->on('incidents');
            $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('approval_records', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $t->foreign('requested_by')->references('id')->on('users')->nullOnDelete();
            $t->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('audit_logs', function (Blueprint $t) {
            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $t->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
        });

        Schema::table('archival_logs', fn (Blueprint $t) => $t->foreign('archived_by')->references('id')->on('users')->nullOnDelete());

        Schema::table('system_configurations', function (Blueprint $t) {
            $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $t->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('system_settings', fn (Blueprint $t) => $t->foreign('updated_by')->references('id')->on('users')->nullOnDelete());
        Schema::table('notifications', fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
    }

    public function down(): void
    {
        // FK objects are dropped together with their tables on rollback /
        // migrate:fresh. Disable checks so column-level drops never block.
        Schema::disableForeignKeyConstraints();
    }
};
