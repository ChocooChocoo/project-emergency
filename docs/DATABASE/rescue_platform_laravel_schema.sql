-- =====================================================================
-- Rescue Platform — Cleaned & Revised Schema (Laravel-ready)
-- =====================================================================
-- Generated 2026-06-25 from rescue_platform_clean_copy.sql
-- Engine: InnoDB | Charset: utf8mb4 / utf8mb4_unicode_ci
--
-- WHAT CHANGED vs the legacy dump:
--   * All tables prefixed with `tbl_`.
--     -> In Laravel set config/database.php: 'prefix' => 'tbl_'
--        Then models/migrations use the UNPREFIXED names (users, incidents...).
--   * Naming cleanups (best practice):
--       - snake_case, plural table names
--       - `password_hash` -> `password`
--       - boolean columns prefixed is_/has_ (er_open->is_er_open,
--         ops_24_7->is_24_7, all_passed->is_all_passed,
--         public_tracking_enabled->is_public_tracking,
--         flagged_for_abuse->is_flagged_for_abuse)
--       - status/enum values normalized to lowercase snake_case
--       - duplicate columns removed (see notes inline)
--   * Applied DATABASE REVISIONS:
--       R1  roles are org-owned (organization_id + unique(organization_id,name))
--       R2  incidents.master_incident_id (heatmap / Master Incident Ticket)
--       R3  ambulances: tier + equipment flags + doh_credential_ref
--       R4  new tbl_device_tokens (anti-abuse UUID strikes)
--       R5  user permissions reference permissions by FK (not loose string)
--       R6  incidents: CHECK not-both(user_id, guest_id)
--       R8  incidents.request_type + scheduled_for
--       R9  new tbl_ad_placements (sustainability)
--       R10 dropped duplicate dispatch timestamp columns
--       R11 organizations slimmed; plan limits live in plans/subscriptions
--       R12 status casing normalized
--       R13 Laravel naming (password, dropped users.full_name)
--   * Tables renamed for clarity:
--       org_subscriptions       -> tbl_organization_subscriptions
--       user_extra_permissions  -> tbl_user_permissions
--       user_medical_history    -> tbl_user_medical_histories
--       ambulance_location_history -> tbl_ambulance_locations
--       geo_aor_layers          -> tbl_geo_layers
--
-- NOTE: *_by columns (created_by, approved_by, ...) are kept as-is
--       (recognizable, avoids churn). Tables created first, then all
--       foreign keys at the end (order-independent, safe to import).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- CORE: identity & access
-- =====================================================================

DROP TABLE IF EXISTS `tbl_users`;
CREATE TABLE `tbl_users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_type` ENUM('citizen','minor','personnel','org_admin','dispatcher','driver','hospital_staff','super_admin') NOT NULL DEFAULT 'citizen',
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `hospital_id` BIGINT UNSIGNED DEFAULT NULL,
  `requested_role` VARCHAR(50) NOT NULL DEFAULT 'user',
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `alt_phone` VARCHAR(50) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,                                  -- R13 (was password_hash)
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `account_status` VARCHAR(50) NOT NULL DEFAULT 'pending_otp',       -- R12
  `id_validation_status` VARCHAR(50) NOT NULL DEFAULT 'not_submitted',
  `guardian_id_validation_status` VARCHAR(50) NOT NULL DEFAULT 'not_required',
  `terms_accepted_at` DATETIME DEFAULT NULL,
  `terms_version` VARCHAR(50) DEFAULT NULL,
  `document_type` VARCHAR(50) DEFAULT NULL,
  `id_number` VARCHAR(100) DEFAULT NULL,
  `email_verified_at` DATETIME DEFAULT NULL,
  `profile_completed_at` DATETIME DEFAULT NULL,
  `registration_source` VARCHAR(50) NOT NULL DEFAULT 'direct',
  `rejected_reason` TEXT DEFAULT NULL,
  `rejection_count` INT NOT NULL DEFAULT 0,
  `suspension_reason` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 1,
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` BIGINT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_org` (`organization_id`),
  KEY `idx_users_hospital` (`hospital_id`),
  KEY `idx_users_status` (`account_status`),
  KEY `idx_users_account_type` (`account_type`),
  KEY `idx_users_approved_by` (`approved_by`),
  KEY `idx_users_archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_roles`;
CREATE TABLE `tbl_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,                   -- R1: NULL = platform/global role
  `name` VARCHAR(100) NOT NULL,
  `scope` ENUM('platform','organization','citizen') NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_org_name` (`organization_id`,`name`),         -- R1
  KEY `idx_roles_scope` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_permissions`;
CREATE TABLE `tbl_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(150) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `module` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_role_permissions`;
CREATE TABLE `tbl_role_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`role_id`,`permission_id`),
  KEY `idx_role_permissions_permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_user_roles`;
CREATE TABLE `tbl_user_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `assigned_by` BIGINT UNSIGNED DEFAULT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role_scope` (`user_id`,`role_id`,`organization_id`),
  KEY `idx_user_roles_org` (`organization_id`),
  KEY `idx_user_roles_role` (`role_id`),
  KEY `idx_user_roles_assigned_by` (`assigned_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_user_permissions`;                          -- R5 (was user_extra_permissions)
CREATE TABLE `tbl_user_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,                          -- R5: FK instead of permission_code string
  `granted_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_org_permission` (`user_id`,`organization_id`,`permission_id`),
  KEY `idx_user_permissions_org` (`organization_id`),
  KEY `idx_user_permissions_permission` (`permission_id`),
  KEY `idx_user_permissions_granted_by` (`granted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_user_sessions`;
CREATE TABLE `tbl_user_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_sessions_token` (`token_hash`),
  KEY `idx_user_sessions_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_user_google_identities`;
CREATE TABLE `tbl_user_google_identities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `google_sub` VARCHAR(255) NOT NULL,
  `email_at_link` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_google_sub` (`google_sub`),
  UNIQUE KEY `uq_user_google_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_email_verification_codes`;
CREATE TABLE `tbl_email_verification_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `attempt_count` INT NOT NULL DEFAULT 0,
  `last_attempt_at` DATETIME DEFAULT NULL,
  `request_ip` VARCHAR(45) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `sent_at` DATETIME DEFAULT current_timestamp(),
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_verification_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_password_reset_codes`;
CREATE TABLE `tbl_password_reset_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `code_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_reset_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_terms_acceptance_logs`;
CREATE TABLE `tbl_terms_acceptance_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `terms_version` VARCHAR(50) NOT NULL,
  `accepted_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_terms_logs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_device_tokens`;                             -- R4 (new)
CREATE TABLE `tbl_device_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_uuid` VARCHAR(128) NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `false_alarm_count` INT NOT NULL DEFAULT 0,
  `last_flagged_at` DATETIME DEFAULT NULL,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `blocked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_tokens_uuid` (`device_uuid`),
  KEY `idx_device_tokens_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- CITIZEN profile & medical
-- =====================================================================

DROP TABLE IF EXISTS `tbl_citizen_profiles`;
CREATE TABLE `tbl_citizen_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `birth_date` DATE DEFAULT NULL,                                    -- dropped duplicate date_of_birth
  `is_minor` TINYINT(1) NOT NULL DEFAULT 0,
  `sex` VARCHAR(20) DEFAULT NULL,
  `address_line` TEXT DEFAULT NULL,                                  -- dropped duplicate generic `address`
  `barangay` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT 'Dasmarinas',
  `province` VARCHAR(100) DEFAULT 'Cavite',
  `birth_place` VARCHAR(255) DEFAULT NULL,
  `emergency_contact_name` VARCHAR(255) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `license_no` VARCHAR(100) DEFAULT NULL,
  `hospital_name` VARCHAR(150) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `position_title` VARCHAR(100) DEFAULT NULL,
  `id_type` VARCHAR(100) DEFAULT NULL,
  `id_number` VARCHAR(100) DEFAULT NULL,
  `id_image_path` VARCHAR(255) DEFAULT NULL,
  `encrypted_sensitive_json` LONGTEXT DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_citizen_profiles_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_user_medical_histories`;
CREATE TABLE `tbl_user_medical_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `blood_type` VARCHAR(10) DEFAULT NULL,
  `allergies` TEXT DEFAULT NULL,
  `chronic_conditions` TEXT DEFAULT NULL,
  `medications` TEXT DEFAULT NULL,
  `surgeries` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `encrypted_payload` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_medical_history_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_user_identity_documents`;
CREATE TABLE `tbl_user_identity_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `document_number` VARCHAR(100) DEFAULT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `document_hash` CHAR(64) DEFAULT NULL,
  `validation_provider` VARCHAR(100) DEFAULT NULL,
  `validation_status` VARCHAR(50) NOT NULL DEFAULT 'pending',        -- R12
  `validation_details_json` LONGTEXT DEFAULT NULL CHECK (json_valid(`validation_details_json`)),
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `submitted_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `validated_at` DATETIME DEFAULT NULL,                              -- dropped duplicate verified_at/verified_by
  `validated_by` BIGINT UNSIGNED DEFAULT NULL,
  `archived_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uid_user` (`user_id`),
  KEY `idx_uid_status` (`validation_status`),
  KEY `idx_uid_validated_by` (`validated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_guardian_links`;
CREATE TABLE `tbl_guardian_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `minor_user_id` BIGINT UNSIGNED NOT NULL,
  `guardian_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `guardian_name` VARCHAR(255) NOT NULL,
  `guardian_relationship` VARCHAR(100) NOT NULL,                     -- dropped legacy alias `relationship`
  `guardian_contact_number` VARCHAR(50) NOT NULL,
  `guardian_email` VARCHAR(150) DEFAULT NULL,
  `guardian_document_id` BIGINT UNSIGNED DEFAULT NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `verified_status` VARCHAR(50) NOT NULL DEFAULT 'pending',          -- R12
  `verified_at` DATETIME DEFAULT NULL,
  `verified_by` BIGINT UNSIGNED DEFAULT NULL,
  `declared_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_guardian_minor` (`minor_user_id`),
  KEY `idx_guardian_user` (`guardian_user_id`),
  KEY `idx_guardian_document` (`guardian_document_id`),
  KEY `idx_guardian_verified_by` (`verified_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_guest_sessions`;
CREATE TABLE `tbl_guest_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guest_key` VARCHAR(64) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `ip_first_seen` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `requests_limit` INT UNSIGNED NOT NULL DEFAULT 2,
  `requests_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `upgraded_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_seen_at` DATETIME DEFAULT NULL,
  `disabled_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_guest_sessions_key` (`guest_key`),
  KEY `idx_guest_sessions_upgraded_user` (`upgraded_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_account_flags`;
CREATE TABLE `tbl_account_flags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `flag_type` ENUM('misuse','false_alarm','identity_issue','duplicate_account','security_review','manual_review') NOT NULL,
  `reason` TEXT NOT NULL,
  `source_model` VARCHAR(100) DEFAULT NULL,
  `source_id` BIGINT UNSIGNED DEFAULT NULL,
  `active_from` DATETIME NOT NULL DEFAULT current_timestamp(),
  `active_until` DATETIME DEFAULT NULL,
  `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `resolved_by` BIGINT UNSIGNED DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_account_flags_user` (`user_id`),
  KEY `idx_account_flags_resolved` (`is_resolved`),
  KEY `idx_account_flags_resolved_by` (`resolved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- ORGANIZATIONS, plans & subscriptions
-- =====================================================================

DROP TABLE IF EXISTS `tbl_plans`;
CREATE TABLE `tbl_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `billing_cycle` ENUM('monthly','yearly','one_time','custom') NOT NULL DEFAULT 'monthly',
  `max_dispatchers` INT DEFAULT NULL,
  `max_drivers` INT DEFAULT NULL,
  `max_ambulances` INT DEFAULT NULL,
  `max_hospitals` INT DEFAULT NULL,
  `max_members` INT DEFAULT NULL,
  `max_roles_assignable` INT DEFAULT NULL,
  `is_unlimited` TINYINT(1) NOT NULL DEFAULT 0,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plans_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- R11: slimmed — subscription/plan-limit columns removed; they live in
--      tbl_plans (limits) and tbl_organization_subscriptions (state).
DROP TABLE IF EXISTS `tbl_organizations`;
CREATE TABLE `tbl_organizations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_type` VARCHAR(50) NOT NULL DEFAULT 'lgu',
  `name` VARCHAR(150) NOT NULL,
  `org_acronym` VARCHAR(80) DEFAULT NULL,
  `registration_permit_number` VARCHAR(120) DEFAULT NULL,
  `code` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `dispatch_hotline_ops` VARCHAR(50) DEFAULT NULL,
  `base_station_location` TEXT DEFAULT NULL,
  `hospital_partners` TEXT DEFAULT NULL,
  `onboarding_reviewer_notes` TEXT DEFAULT NULL,
  `ambulance_count` INT DEFAULT NULL,
  `service_type` VARCHAR(100) DEFAULT NULL,
  `is_24_7` TINYINT(1) NOT NULL DEFAULT 0,                           -- was ops_24_7
  `admin_contact_title` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `service_city` VARCHAR(100) NOT NULL DEFAULT 'Dasmariñas',
  `covered_barangays_json` TEXT DEFAULT NULL,
  `org_zone_type` VARCHAR(150) DEFAULT NULL,
  `coverage_jurisdiction` VARCHAR(40) DEFAULT NULL,
  `service_radius_km` DECIMAL(6,2) DEFAULT NULL,
  `hq_latitude` DECIMAL(10,7) DEFAULT NULL,
  `hq_longitude` DECIMAL(10,7) DEFAULT NULL,
  `coverage_summary` TEXT DEFAULT NULL,
  `registration_type` VARCHAR(50) DEFAULT NULL,
  `organization_identity` VARCHAR(40) DEFAULT NULL COMMENT 'public_hospital|private_hospital|lgu_city_wide|admin_zone_barangay|ngo_operational',
  `organization_status` VARCHAR(50) NOT NULL DEFAULT 'pending_review', -- R12
  `registration_status` VARCHAR(50) DEFAULT NULL,
  `admin_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `aor_mode` VARCHAR(30) NOT NULL DEFAULT 'exclusive',
  `auto_progression_radius_m` INT NOT NULL DEFAULT 50,
  `response_target_minutes` INT UNSIGNED DEFAULT 8,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `rejected_reason` TEXT DEFAULT NULL,
  `org_profile_completed_at` DATETIME DEFAULT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` BIGINT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_organizations_code` (`code`),
  KEY `idx_organizations_status` (`organization_status`),
  KEY `idx_organizations_registration_status` (`registration_status`),
  KEY `idx_organizations_active` (`is_active`),
  KEY `idx_organizations_admin_user` (`admin_user_id`),
  KEY `idx_organizations_approved_by` (`approved_by`),
  KEY `idx_organizations_archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_organization_subscriptions`;               -- was org_subscriptions
CREATE TABLE `tbl_organization_subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('trialing','active','past_due','cancelled','paused') NOT NULL DEFAULT 'trialing',
  `payment_confirmed_at` DATETIME DEFAULT NULL,
  `current_period_start` DATE DEFAULT NULL,
  `current_period_end` DATE DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_subscriptions_org` (`organization_id`),
  KEY `idx_org_subscriptions_plan` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_subscription_payments`;
CREATE TABLE `tbl_subscription_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,                    -- dropped legacy duplicate org_id
  `plan_id` BIGINT UNSIGNED DEFAULT NULL,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'mock',
  `provider_reference` VARCHAR(255) DEFAULT NULL,
  `provider_event_id` VARCHAR(255) DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'PHP',
  `status` ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sub_payments_org` (`organization_id`),
  KEY `idx_sub_payments_plan` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_organization_coverage_areas`;
CREATE TABLE `tbl_organization_coverage_areas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `coverage_name` VARCHAR(150) DEFAULT NULL,
  `area_name` VARCHAR(150) DEFAULT NULL,
  `barangay_name` VARCHAR(100) DEFAULT NULL,
  `polygon_json` LONGTEXT DEFAULT NULL CHECK (json_valid(`polygon_json`)),
  `coordinates_json` LONGTEXT DEFAULT NULL CHECK (json_valid(`coordinates_json`)),
  `priority_rank` INT NOT NULL DEFAULT 1,
  `is_overlap_allowed` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coverage_org` (`organization_id`),
  KEY `idx_coverage_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_organization_documents`;
CREATE TABLE `tbl_organization_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `document_type` VARCHAR(100) NOT NULL,
  `document_number` VARCHAR(100) DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `validation_status` VARCHAR(50) NOT NULL DEFAULT 'pending',        -- R12
  `is_optional` TINYINT(1) NOT NULL DEFAULT 0,
  `validated_by` BIGINT UNSIGNED DEFAULT NULL,
  `validated_at` DATETIME DEFAULT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_org_documents_org` (`organization_id`),
  KEY `idx_org_documents_type` (`document_type`),
  KEY `idx_org_documents_validation` (`validation_status`),
  KEY `idx_org_documents_validated_by` (`validated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_geo_layers`;                                -- was geo_aor_layers
CREATE TABLE `tbl_geo_layers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(64) NOT NULL,
  `label` VARCHAR(200) NOT NULL DEFAULT '',
  `geojson` LONGTEXT NOT NULL,
  `source_url` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_geo_layers_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- FLEET
-- =====================================================================

DROP TABLE IF EXISTS `tbl_ambulances`;
CREATE TABLE `tbl_ambulances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `plate_no` VARCHAR(50) NOT NULL,
  `unit_code` VARCHAR(50) DEFAULT NULL,
  `vehicle_name` VARCHAR(100) DEFAULT NULL,
  `vehicle_type` VARCHAR(100) DEFAULT NULL,
  `tier` ENUM('bls','als') DEFAULT NULL COMMENT 'R3: Type 1 BLS / Type 2 ALS',
  `doh_credential_ref` VARCHAR(100) DEFAULT NULL COMMENT 'R3',
  `has_ventilator` TINYINT(1) NOT NULL DEFAULT 0,                    -- R3 equipment flags
  `has_oxygen` TINYINT(1) NOT NULL DEFAULT 0,
  `has_aed` TINYINT(1) NOT NULL DEFAULT 0,
  `has_spine_board` TINYINT(1) NOT NULL DEFAULT 0,
  `has_ob_kit` TINYINT(1) NOT NULL DEFAULT 0,
  `has_stretcher` TINYINT(1) NOT NULL DEFAULT 0,
  `capacity_patients` INT NOT NULL DEFAULT 1,
  `equipment_notes` TEXT DEFAULT NULL,
  `current_driver_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `readiness_status` VARCHAR(50) NOT NULL DEFAULT 'ready',           -- R12
  `status` VARCHAR(50) NOT NULL DEFAULT 'available',                 -- R12
  `is_serviceable` TINYINT(1) NOT NULL DEFAULT 1,
  `current_odometer_km` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `next_maintenance_date` DATE DEFAULT NULL,
  `last_lat` DECIMAL(10,8) DEFAULT NULL,
  `last_lng` DECIMAL(11,8) DEFAULT NULL,
  `last_seen_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` BIGINT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ambulances_org_plate` (`organization_id`,`plate_no`),
  KEY `idx_ambulances_status` (`status`),
  KEY `idx_ambulances_tier` (`tier`),
  KEY `idx_ambulances_current_driver` (`current_driver_user_id`),
  KEY `idx_ambulances_archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_ambulance_locations`;                      -- was ambulance_location_history
CREATE TABLE `tbl_ambulance_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ambulance_id` BIGINT UNSIGNED NOT NULL,
  `dispatch_assignment_id` BIGINT UNSIGNED DEFAULT NULL,
  `lat` DECIMAL(10,7) NOT NULL,
  `lng` DECIMAL(10,7) NOT NULL,
  `recorded_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ambulance_location_time` (`ambulance_id`,`recorded_at`),
  KEY `idx_ambulance_location_assignment` (`dispatch_assignment_id`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_ambulance_status_logs`;
CREATE TABLE `tbl_ambulance_status_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ambulance_id` BIGINT UNSIGNED NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `changed_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ambulance_status_logs_ambulance` (`ambulance_id`),
  KEY `idx_ambulance_status_logs_changed_by` (`changed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_fuel_logs`;
CREATE TABLE `tbl_fuel_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ambulance_id` BIGINT UNSIGNED NOT NULL,
  `log_date` DATE NOT NULL,
  `liters` DECIMAL(10,2) NOT NULL,
  `cost_per_liter` DECIMAL(10,2) DEFAULT NULL,
  `total_cost` DECIMAL(10,2) DEFAULT NULL,
  `odometer_km` INT DEFAULT NULL,
  `fuel_type` ENUM('diesel','gasoline','premium','other') NOT NULL DEFAULT 'diesel',  -- R12
  `station` VARCHAR(150) DEFAULT NULL,
  `filled_by` VARCHAR(150) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fuel_logs_ambulance_date` (`ambulance_id`,`log_date`),
  KEY `idx_fuel_logs_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_maintenance_logs`;
CREATE TABLE `tbl_maintenance_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ambulance_id` BIGINT UNSIGNED NOT NULL,
  `maintenance_type` ENUM('preventive','corrective','emergency','inspection','tire','oil_change','brake','battery','other') NOT NULL DEFAULT 'preventive',  -- R12
  `description` TEXT NOT NULL,
  `performed_by` VARCHAR(150) DEFAULT NULL,
  `cost` DECIMAL(10,2) DEFAULT NULL,
  `odometer_km` INT DEFAULT NULL,
  `scheduled_date` DATE DEFAULT NULL,
  `performed_date` DATE DEFAULT NULL,
  `next_due_date` DATE DEFAULT NULL,
  `next_due_km` INT DEFAULT NULL,
  `status` ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',  -- R12
  `parts_replaced` TEXT DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `updated_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maintenance_logs_ambulance` (`ambulance_id`),
  KEY `idx_maintenance_logs_status` (`status`),
  KEY `idx_maintenance_logs_created_by` (`created_by`),
  KEY `idx_maintenance_logs_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_unit_readiness_checks`;
CREATE TABLE `tbl_unit_readiness_checks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ambulance_id` BIGINT UNSIGNED NOT NULL,
  `driver_user_id` BIGINT UNSIGNED NOT NULL,
  `check_date` DATE NOT NULL,
  `checks` LONGTEXT NOT NULL CHECK (json_valid(`checks`)),
  `is_all_passed` TINYINT(1) NOT NULL DEFAULT 1,                     -- was all_passed
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unit_readiness_daily` (`ambulance_id`,`driver_user_id`,`check_date`),
  KEY `idx_unit_readiness_driver` (`driver_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_driver_duty_states`;
CREATE TABLE `tbl_driver_duty_states` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `driver_user_id` BIGINT UNSIGNED NOT NULL,
  `ambulance_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('on_duty','off_duty','break') NOT NULL DEFAULT 'off_duty',
  `started_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `created_at` DATETIME DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_driver_duty_user` (`driver_user_id`),
  KEY `idx_driver_duty_ambulance` (`ambulance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- INCIDENTS, dispatch & DSS
-- =====================================================================

DROP TABLE IF EXISTS `tbl_incidents`;
CREATE TABLE `tbl_incidents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_code` VARCHAR(50) NOT NULL,
  `request_type` ENUM('one_tap','detailed','non_emergency','scheduled') NOT NULL DEFAULT 'one_tap',  -- R8 (replaces request_mode)
  `scheduled_for` DATETIME DEFAULT NULL COMMENT 'R8: scheduled rescue time',
  `master_incident_id` BIGINT UNSIGNED DEFAULT NULL COMMENT 'R2: heatmap Master Incident Ticket (self-ref)',
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `guest_id` BIGINT UNSIGNED DEFAULT NULL,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `coverage_area_id` BIGINT UNSIGNED DEFAULT NULL,
  `patient_name` VARCHAR(150) DEFAULT NULL,
  `contact_number` VARCHAR(50) DEFAULT NULL,
  `incident_type` VARCHAR(100) DEFAULT NULL COMMENT 'Emergency category (was `type`)',
  `priority_label` VARCHAR(30) DEFAULT NULL,
  `severity` TINYINT UNSIGNED NOT NULL DEFAULT 4,
  `patient_count` SMALLINT UNSIGNED DEFAULT NULL,
  `pickup_lat` DECIMAL(10,8) DEFAULT NULL,
  `pickup_lng` DECIMAL(11,8) DEFAULT NULL,
  `pickup_address` TEXT NOT NULL,
  `pickup_landmark` VARCHAR(255) DEFAULT NULL,
  `destination_hospital_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('pending','dispatched','ongoing','on_scene','transporting','arrived_at_hospital','completed','cancelled','resolved_on_scene') NOT NULL DEFAULT 'pending',
  `dispatch_routing_state` VARCHAR(64) DEFAULT NULL COMMENT 'pending_dss|routed_to_org|escalated_to_dss|reassign_within_org',
  `dss_org_queue_json` LONGTEXT DEFAULT NULL COMMENT 'JSON array of org ids ordered by DSS',
  `outcome_tag` VARCHAR(50) NOT NULL DEFAULT 'none',                 -- R12
  `is_flagged_for_abuse` TINYINT(1) NOT NULL DEFAULT 0,             -- was flagged_for_abuse
  `is_public_tracking` TINYINT(1) NOT NULL DEFAULT 1,              -- was public_tracking_enabled
  `eta_minutes` INT DEFAULT NULL,
  `request_summary` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `arrived_scene_at` DATETIME DEFAULT NULL,
  `resolved_on_scene_at` DATETIME DEFAULT NULL,
  `transport_started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `approved_by` BIGINT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` BIGINT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_incidents_request_code` (`request_code`),
  KEY `idx_incidents_request_type` (`request_type`),
  KEY `idx_incidents_status` (`status`),
  KEY `idx_incidents_user` (`user_id`),
  KEY `idx_incidents_guest` (`guest_id`),
  KEY `idx_incidents_org` (`organization_id`),
  KEY `idx_incidents_master` (`master_incident_id`),
  KEY `idx_incidents_created_at` (`created_at`),
  KEY `idx_incidents_coverage_area` (`coverage_area_id`),
  KEY `idx_incidents_destination_hospital` (`destination_hospital_id`),
  KEY `idx_incidents_approved_by` (`approved_by`),
  KEY `idx_incidents_archived_by` (`archived_by`),
  CONSTRAINT `chk_incidents_single_requester` CHECK (`user_id` IS NULL OR `guest_id` IS NULL)  -- R6
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_incident_updates`;
CREATE TABLE `tbl_incident_updates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `dispatch_assignment_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL,
  `care_status` VARCHAR(50) DEFAULT NULL,
  `update_type` VARCHAR(32) DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  `visibility` ENUM('private','organization','public') NOT NULL DEFAULT 'organization',
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_incident_updates_incident` (`incident_id`,`created_at`),
  KEY `idx_incident_updates_assignment` (`dispatch_assignment_id`),
  KEY `idx_incident_updates_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_dispatch_assignments`;
CREATE TABLE `tbl_dispatch_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `dispatcher_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `assigned_by` BIGINT UNSIGNED DEFAULT NULL,
  `ambulance_id` BIGINT UNSIGNED NOT NULL,
  `driver_user_id` BIGINT UNSIGNED NOT NULL,
  `hospital_id` BIGINT UNSIGNED DEFAULT NULL,
  `status` ENUM('assigned','accepted','acknowledged','en_route','approaching_scene','arrived_on_scene','departing_scene','approaching_hospital','arrived_hospital','clear_for_duty','timed_out','rejected','reassigned','cancelled','completed','unassigned') NOT NULL DEFAULT 'assigned',
  `care_status` VARCHAR(50) NOT NULL DEFAULT 'awaiting_assessment',  -- R12
  `scene_approach_radius_m` INT NOT NULL DEFAULT 1000,
  `scene_arrival_radius_m` INT NOT NULL DEFAULT 200,
  `facility_approach_radius_m` INT NOT NULL DEFAULT 1000,
  `facility_arrival_radius_m` INT NOT NULL DEFAULT 200,
  `assigned_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `response_deadline_at` DATETIME DEFAULT NULL COMMENT 'Driver must accept by this time',
  `accepted_at` DATETIME DEFAULT NULL,
  `en_route_at` DATETIME DEFAULT NULL,
  `approaching_scene_at` DATETIME DEFAULT NULL,
  `arrived_on_scene_at` DATETIME DEFAULT NULL,                       -- R10 (dropped duplicate arrived_at_scene_at)
  `departed_scene_at` DATETIME DEFAULT NULL,
  `transport_started_at` DATETIME DEFAULT NULL,
  `near_destination_at` DATETIME DEFAULT NULL,
  `arrived_at_hospital_at` DATETIME DEFAULT NULL,                    -- R10 (dropped duplicate arrived_at_facility_at)
  `handover_completed_at` DATETIME DEFAULT NULL,
  `timed_out_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `ended_at` DATETIME DEFAULT NULL,
  `forwarded_from_assignment_id` BIGINT UNSIGNED DEFAULT NULL,
  `alert_attempts` INT NOT NULL DEFAULT 0,
  `dss_rank` INT DEFAULT NULL,
  `dispatch_notes` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` BIGINT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispatch_incident` (`incident_id`),
  KEY `idx_dispatch_status` (`status`),
  KEY `idx_dispatch_care_status` (`care_status`),
  KEY `idx_dispatch_driver` (`driver_user_id`),
  KEY `idx_dispatch_ambulance` (`ambulance_id`),
  KEY `idx_dispatch_org` (`organization_id`),
  KEY `idx_dispatch_dispatcher` (`dispatcher_user_id`),
  KEY `idx_dispatch_assigned_by` (`assigned_by`),
  KEY `idx_dispatch_hospital` (`hospital_id`),
  KEY `idx_dispatch_forwarded_from` (`forwarded_from_assignment_id`),
  KEY `idx_dispatch_archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_driver_completion_reports`;
CREATE TABLE `tbl_driver_completion_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `ambulance_id` BIGINT UNSIGNED NOT NULL,
  `driver_user_id` BIGINT UNSIGNED NOT NULL,
  `scene_summary` TEXT DEFAULT NULL,
  `first_aid_summary` TEXT DEFAULT NULL,
  `transport_summary` TEXT DEFAULT NULL,
  `handover_summary` TEXT DEFAULT NULL,
  `outcome_notes` TEXT DEFAULT NULL,
  `patient_status` ENUM('stable','critical','deceased','refused_transport','resolved_on_scene','other') DEFAULT NULL,
  `odometer_end` DECIMAL(10,2) DEFAULT NULL,
  `review_status` VARCHAR(30) NOT NULL DEFAULT 'pending',            -- R12
  `reviewed_by` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `extra_notes` TEXT DEFAULT NULL,
  `submitted_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_completion_assignment` (`assignment_id`),
  KEY `idx_completion_review_status` (`review_status`),
  KEY `idx_completion_incident` (`incident_id`),
  KEY `idx_completion_ambulance` (`ambulance_id`),
  KEY `idx_completion_driver` (`driver_user_id`),
  KEY `idx_completion_reviewed_by` (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_request_outcome_logs`;
CREATE TABLE `tbl_request_outcome_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `tag` VARCHAR(50) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `severity` VARCHAR(20) NOT NULL DEFAULT 'medium',                  -- R12
  `action_taken` VARCHAR(150) DEFAULT NULL,
  `outcome_tag` VARCHAR(50) DEFAULT NULL,
  `offense_level` INT NOT NULL DEFAULT 0,
  `internal_notes` TEXT DEFAULT NULL,
  `logged_by_id` BIGINT UNSIGNED DEFAULT NULL,
  `tagged_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `tagged_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_request_outcome_incident` (`incident_id`),
  KEY `idx_request_outcome_logged_by` (`logged_by_id`),
  KEY `idx_request_outcome_tagged_by` (`tagged_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- HOSPITALS & medical records
-- =====================================================================

DROP TABLE IF EXISTS `tbl_hospitals`;
CREATE TABLE `tbl_hospitals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `facility_type` VARCHAR(50) NOT NULL DEFAULT 'hospital',
  `ownership` VARCHAR(50) DEFAULT NULL,
  `ambulance_status` VARCHAR(80) DEFAULT 'needs_direct_verification',
  `city` VARCHAR(100) DEFAULT 'Dasmariñas',
  `province` VARCHAR(100) DEFAULT 'Cavite',
  `hotlines_json` TEXT DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `lat` DECIMAL(10,8) DEFAULT NULL,
  `lng` DECIMAL(11,8) DEFAULT NULL,
  `capacity_status` VARCHAR(50) NOT NULL DEFAULT 'unknown',          -- R12
  `available_beds` INT DEFAULT NULL,
  `is_er_open` TINYINT(1) NOT NULL DEFAULT 1,                        -- was er_open
  `notes` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_archived` TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at` DATETIME DEFAULT NULL,
  `archived_by` BIGINT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_hospitals_org` (`organization_id`),
  KEY `idx_hospitals_active` (`is_active`),
  KEY `idx_hospitals_created_by` (`created_by`),
  KEY `idx_hospitals_archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_hospital_endorsements`;
CREATE TABLE `tbl_hospital_endorsements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dispatch_assignment_id` BIGINT UNSIGNED DEFAULT NULL,
  `incident_id` BIGINT UNSIGNED DEFAULT NULL,
  `hospital_id` BIGINT UNSIGNED NOT NULL,
  `endorsed_by` BIGINT UNSIGNED DEFAULT NULL,
  `received_by` BIGINT UNSIGNED DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',                   -- R12
  `responded_by` BIGINT UNSIGNED DEFAULT NULL,
  `responded_at` DATETIME DEFAULT NULL,
  `response_notes` TEXT DEFAULT NULL,                                -- dropped duplicate response_note
  `received_at` DATETIME DEFAULT NULL,
  `arrived_at` DATETIME DEFAULT NULL,
  `handoff_confirmed_at` DATETIME DEFAULT NULL,
  `outcome_note` TEXT DEFAULT NULL,
  `handoff_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_endorsements_incident` (`incident_id`),
  KEY `idx_endorsements_assignment` (`dispatch_assignment_id`),
  KEY `idx_endorsements_hospital` (`hospital_id`),
  KEY `idx_endorsements_endorsed_by` (`endorsed_by`),
  KEY `idx_endorsements_received_by` (`received_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_handoff_summaries`;
CREATE TABLE `tbl_handoff_summaries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `summary` TEXT NOT NULL,
  `outcome` VARCHAR(150) DEFAULT NULL,
  `handoff_to` VARCHAR(150) DEFAULT NULL,
  `handoff_at` DATETIME DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_handoff_summary_incident` (`incident_id`),
  KEY `idx_handoff_summary_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_patients`;
CREATE TABLE `tbl_patients` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `full_name` VARCHAR(150) DEFAULT NULL,
  `sex` VARCHAR(20) DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,                                    -- was birthdate
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patients_incident` (`incident_id`),
  KEY `idx_patients_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_vitals_entries`;
CREATE TABLE `tbl_vitals_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `recorded_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `bp_systolic` SMALLINT UNSIGNED DEFAULT NULL,
  `bp_diastolic` SMALLINT UNSIGNED DEFAULT NULL,
  `pulse_rate` SMALLINT UNSIGNED DEFAULT NULL,
  `respiratory_rate` SMALLINT UNSIGNED DEFAULT NULL,
  `temperature_c` DECIMAL(4,1) DEFAULT NULL,
  `oxygen_saturation` SMALLINT UNSIGNED DEFAULT NULL,
  `blood_glucose` DECIMAL(6,2) DEFAULT NULL,
  `gcs_score` TINYINT UNSIGNED DEFAULT NULL,
  `pain_score` TINYINT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vitals_incident_time` (`incident_id`,`recorded_at`),
  KEY `idx_vitals_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_treatment_records`;
CREATE TABLE `tbl_treatment_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `performed_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `treatment_type` VARCHAR(150) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_treatment_incident_time` (`incident_id`,`performed_at`),
  KEY `idx_treatment_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_prehospital_notes`;
CREATE TABLE `tbl_prehospital_notes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` BIGINT UNSIGNED NOT NULL,
  `note_type` VARCHAR(100) NOT NULL DEFAULT 'general',
  `content` TEXT NOT NULL,
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prehospital_notes_incident` (`incident_id`,`created_at`),
  KEY `idx_prehospital_notes_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SYSTEM, approvals, audit & sustainability
-- =====================================================================

DROP TABLE IF EXISTS `tbl_approval_records`;
CREATE TABLE `tbl_approval_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_type` ENUM('user','organization','completion_report','document','other') NOT NULL,
  `target_id` BIGINT UNSIGNED NOT NULL,
  `request_type` VARCHAR(100) NOT NULL,
  `status` ENUM('pending','approved','rejected','returned') NOT NULL DEFAULT 'pending',
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `requested_by` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_by` BIGINT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `payload_json` LONGTEXT DEFAULT NULL CHECK (json_valid(`payload_json`)),
  `requested_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_approval_target` (`target_type`,`target_id`),
  KEY `idx_approval_status` (`status`),
  KEY `idx_approval_org` (`organization_id`),
  KEY `idx_approval_requested_by` (`requested_by`),
  KEY `idx_approval_reviewed_by` (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_audit_logs`;
CREATE TABLE `tbl_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `role` VARCHAR(50) DEFAULT NULL,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(150) NOT NULL,
  `model_type` VARCHAR(100) DEFAULT NULL,
  `model_id` BIGINT UNSIGNED DEFAULT NULL,
  `new_values` LONGTEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_user_created` (`user_id`,`created_at`),
  KEY `idx_audit_logs_model` (`model_type`,`model_id`),
  KEY `idx_audit_logs_org` (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_archival_logs`;
CREATE TABLE `tbl_archival_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(100) NOT NULL,
  `record_id` BIGINT UNSIGNED NOT NULL,
  `archived_by` BIGINT UNSIGNED DEFAULT NULL,
  `archive_reason` TEXT DEFAULT NULL,
  `archived_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `snapshot_json` LONGTEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_archival_logs_table_record` (`table_name`,`record_id`),
  KEY `idx_archival_logs_archived_by` (`archived_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_system_logs`;
CREATE TABLE `tbl_system_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(20) NOT NULL,
  `category` VARCHAR(50) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `context` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_system_logs_level_created` (`level`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_system_configurations`;
CREATE TABLE `tbl_system_configurations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` ENUM('global','organization') NOT NULL DEFAULT 'global',
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `config_key` VARCHAR(100) NOT NULL,
  `config_value` TEXT NOT NULL,
  `config_type` ENUM('string','int','float','boolean','json') NOT NULL DEFAULT 'string',
  `description` TEXT DEFAULT NULL,
  `updated_by` BIGINT UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_system_config_scope_key` (`scope`,`organization_id`,`config_key`),
  KEY `idx_system_config_org` (`organization_id`),
  KEY `idx_system_config_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_system_settings`;
CREATE TABLE `tbl_system_settings` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `settings_json` LONGTEXT NOT NULL CHECK (json_valid(`settings_json`)),
  `updated_by` BIGINT UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_notifications`;
CREATE TABLE `tbl_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(100) NOT NULL DEFAULT 'system',
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `data_json` LONGTEXT DEFAULT NULL CHECK (json_valid(`data_json`)),
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tbl_ad_placements`;                            -- R9 (new)
CREATE TABLE `tbl_ad_placements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slot` VARCHAR(100) NOT NULL,
  `title` VARCHAR(150) DEFAULT NULL,
  `content` TEXT DEFAULT NULL,
  `target_url` VARCHAR(500) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_emergency_safe` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Must be excluded from active emergency UI',
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ad_placements_slot_active` (`slot`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- FOREIGN KEYS (added last — order-independent)
-- =====================================================================

ALTER TABLE `tbl_users`
  ADD CONSTRAINT `fk_users_organization` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `tbl_hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_roles`
  ADD CONSTRAINT `fk_roles_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE;  -- R1

ALTER TABLE `tbl_role_permissions`
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_user_roles`
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_roles_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_roles_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_user_permissions`
  ADD CONSTRAINT `fk_user_permissions_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_permissions_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`id`) ON DELETE CASCADE,  -- R5
  ADD CONSTRAINT `fk_user_permissions_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_user_sessions`
  ADD CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_user_google_identities`
  ADD CONSTRAINT `fk_user_google_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_email_verification_codes`
  ADD CONSTRAINT `fk_email_verification_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_password_reset_codes`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_terms_acceptance_logs`
  ADD CONSTRAINT `fk_terms_logs_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_device_tokens`
  ADD CONSTRAINT `fk_device_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_citizen_profiles`
  ADD CONSTRAINT `fk_citizen_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_user_medical_histories`
  ADD CONSTRAINT `fk_user_medical_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_user_identity_documents`
  ADD CONSTRAINT `fk_uid_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uid_validated_by` FOREIGN KEY (`validated_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_guardian_links`
  ADD CONSTRAINT `fk_guardian_minor` FOREIGN KEY (`minor_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_guardian_user` FOREIGN KEY (`guardian_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_guardian_document` FOREIGN KEY (`guardian_document_id`) REFERENCES `tbl_user_identity_documents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_guardian_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_guest_sessions`
  ADD CONSTRAINT `fk_guest_sessions_upgraded_user` FOREIGN KEY (`upgraded_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_account_flags`
  ADD CONSTRAINT `fk_account_flags_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_account_flags_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_organizations`
  ADD CONSTRAINT `fk_organizations_admin_user` FOREIGN KEY (`admin_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_organizations_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_organizations_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_organization_subscriptions`
  ADD CONSTRAINT `fk_org_subscriptions_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_org_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `tbl_plans` (`id`);

ALTER TABLE `tbl_subscription_payments`
  ADD CONSTRAINT `fk_sub_payments_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sub_payments_plan` FOREIGN KEY (`plan_id`) REFERENCES `tbl_plans` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_organization_coverage_areas`
  ADD CONSTRAINT `fk_coverage_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_organization_documents`
  ADD CONSTRAINT `fk_org_documents_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_org_documents_validated_by` FOREIGN KEY (`validated_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_ambulances`
  ADD CONSTRAINT `fk_ambulances_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`),
  ADD CONSTRAINT `fk_ambulances_current_driver` FOREIGN KEY (`current_driver_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ambulances_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_ambulance_locations`
  ADD CONSTRAINT `fk_ambulance_locations_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ambulance_locations_assignment` FOREIGN KEY (`dispatch_assignment_id`) REFERENCES `tbl_dispatch_assignments` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_ambulance_status_logs`
  ADD CONSTRAINT `fk_ambulance_status_logs_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ambulance_status_logs_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_fuel_logs`
  ADD CONSTRAINT `fk_fuel_logs_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fuel_logs_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_maintenance_logs`
  ADD CONSTRAINT `fk_maintenance_logs_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_maintenance_logs_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_maintenance_logs_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_unit_readiness_checks`
  ADD CONSTRAINT `fk_unit_readiness_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_unit_readiness_driver` FOREIGN KEY (`driver_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tbl_driver_duty_states`
  ADD CONSTRAINT `fk_driver_duty_user` FOREIGN KEY (`driver_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_driver_duty_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_incidents`
  ADD CONSTRAINT `fk_incidents_master` FOREIGN KEY (`master_incident_id`) REFERENCES `tbl_incidents` (`id`) ON DELETE SET NULL,  -- R2
  ADD CONSTRAINT `fk_incidents_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incidents_guest` FOREIGN KEY (`guest_id`) REFERENCES `tbl_guest_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incidents_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incidents_coverage_area` FOREIGN KEY (`coverage_area_id`) REFERENCES `tbl_organization_coverage_areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incidents_destination_hospital` FOREIGN KEY (`destination_hospital_id`) REFERENCES `tbl_hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incidents_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incidents_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_incident_updates`
  ADD CONSTRAINT `fk_incident_updates_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_incident_updates_assignment` FOREIGN KEY (`dispatch_assignment_id`) REFERENCES `tbl_dispatch_assignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_incident_updates_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_dispatch_assignments`
  ADD CONSTRAINT `fk_dispatch_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_dispatch_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`),
  ADD CONSTRAINT `fk_dispatch_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`),
  ADD CONSTRAINT `fk_dispatch_driver` FOREIGN KEY (`driver_user_id`) REFERENCES `tbl_users` (`id`),
  ADD CONSTRAINT `fk_dispatch_dispatcher` FOREIGN KEY (`dispatcher_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dispatch_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dispatch_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `tbl_hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dispatch_forwarded_from` FOREIGN KEY (`forwarded_from_assignment_id`) REFERENCES `tbl_dispatch_assignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dispatch_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_driver_completion_reports`
  ADD CONSTRAINT `fk_completion_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `tbl_dispatch_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_completion_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_completion_ambulance` FOREIGN KEY (`ambulance_id`) REFERENCES `tbl_ambulances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_completion_driver` FOREIGN KEY (`driver_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_completion_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_request_outcome_logs`
  ADD CONSTRAINT `fk_request_outcome_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_request_outcome_tagged_by` FOREIGN KEY (`tagged_by_user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_hospitals`
  ADD CONSTRAINT `fk_hospitals_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_hospitals_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_hospitals_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_hospital_endorsements`
  ADD CONSTRAINT `fk_endorsements_assignment` FOREIGN KEY (`dispatch_assignment_id`) REFERENCES `tbl_dispatch_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_endorsements_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_endorsements_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `tbl_hospitals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_endorsements_endorsed_by` FOREIGN KEY (`endorsed_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_endorsements_received_by` FOREIGN KEY (`received_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_handoff_summaries`
  ADD CONSTRAINT `fk_handoff_summary_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_handoff_summary_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_patients`
  ADD CONSTRAINT `fk_patients_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_patients_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_vitals_entries`
  ADD CONSTRAINT `fk_vitals_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_vitals_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_treatment_records`
  ADD CONSTRAINT `fk_treatment_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_treatment_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_prehospital_notes`
  ADD CONSTRAINT `fk_prehospital_notes_incident` FOREIGN KEY (`incident_id`) REFERENCES `tbl_incidents` (`id`),
  ADD CONSTRAINT `fk_prehospital_notes_created_by` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_approval_records`
  ADD CONSTRAINT `fk_approval_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_approval_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_approval_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_audit_logs_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_archival_logs`
  ADD CONSTRAINT `fk_archival_logs_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_system_configurations`
  ADD CONSTRAINT `fk_system_config_org` FOREIGN KEY (`organization_id`) REFERENCES `tbl_organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_system_config_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_system_settings`
  ADD CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tbl_notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
-- End of schema.
