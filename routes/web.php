<?php

use App\Http\Controllers\Admin\AmbulanceController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\CareController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DispatchController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\HospitalController;
use App\Http\Controllers\Admin\IncidentController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\OrgApprovalController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SafetyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Intake\RequestIntakeController;
use Illuminate\Support\Facades\Route;

// --- Guest auth routes ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])
        ->middleware('throttle:6,1');

    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:6,1');

    // Email OTP verification (post-register; identity carried in session).
    Route::get('/verify-email', [VerifyEmailController::class, 'show'])->name('verify-email.show');
    Route::post('/verify-email', [VerifyEmailController::class, 'verify'])
        ->name('verify-email.verify')->middleware('throttle:10,1');
    Route::post('/verify-email/resend', [VerifyEmailController::class, 'resend'])
        ->name('verify-email.resend')->middleware('throttle:3,1');

    // Password reset.
    Route::get('/forgot-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])
        ->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update')->middleware('throttle:6,1');
});

// --- Authenticated routes ---
Route::middleware(['auth', 'account.active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can.perm:access-admin')->name('dashboard');

    // S3 — Super Admin modules.
    Route::middleware('can.perm:manage-users')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::patch('/admin/users/{user}/active', [UserController::class, 'toggleActive'])->name('admin.users.active');
        Route::patch('/admin/users/{user}/archive', [UserController::class, 'archive'])->name('admin.users.archive');
        Route::patch('/admin/users/{user}/restore', [UserController::class, 'restore'])->name('admin.users.restore');
    });

    Route::middleware('can.perm:review-approvals')->group(function () {
        Route::get('/admin/approvals', [ApprovalController::class, 'index'])->name('admin.approvals.index');
        Route::patch('/admin/approvals/{user}/approve', [ApprovalController::class, 'approve'])->name('admin.approvals.approve');
        Route::patch('/admin/approvals/{user}/reject', [ApprovalController::class, 'reject'])->name('admin.approvals.reject');
    });

    // S4 — Organizations & Onboarding.
    Route::middleware('can.perm:manage-organizations')->group(function () {
        Route::get('/admin/organizations', [OrganizationController::class, 'index'])->name('admin.organizations.index');
        Route::get('/admin/organizations/create', [OrganizationController::class, 'create'])->name('admin.organizations.create');
        Route::post('/admin/organizations', [OrganizationController::class, 'store'])->name('admin.organizations.store');
        Route::get('/admin/organizations/{organization}', [OrganizationController::class, 'show'])->name('admin.organizations.show');
        Route::get('/admin/organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('admin.organizations.edit');
        Route::put('/admin/organizations/{organization}', [OrganizationController::class, 'update'])->name('admin.organizations.update');
        Route::patch('/admin/organizations/{organization}/archive', [OrganizationController::class, 'archive'])->name('admin.organizations.archive');
        Route::patch('/admin/organizations/{organization}/restore', [OrganizationController::class, 'restore'])->name('admin.organizations.restore');
    });

    Route::middleware('can.perm:review-org-approvals')->group(function () {
        Route::get('/admin/org-approvals', [OrgApprovalController::class, 'index'])->name('admin.org-approvals.index');
        Route::get('/admin/org-approvals/{organization}', [OrgApprovalController::class, 'show'])->name('admin.org-approvals.show');
        Route::patch('/admin/org-approvals/{organization}/approve', [OrgApprovalController::class, 'approve'])->name('admin.org-approvals.approve');
        Route::patch('/admin/org-approvals/{organization}/reject', [OrgApprovalController::class, 'reject'])->name('admin.org-approvals.reject');
        Route::patch('/admin/org-approvals/{organization}/documents/{document}/status', [OrgApprovalController::class, 'updateDocumentStatus'])->name('admin.org-approvals.documents.status');
    });

    // S5 — Fleet.
    Route::middleware('can.perm:manage-fleet')->group(function () {
        Route::get('/admin/ambulances', [AmbulanceController::class, 'index'])->name('admin.ambulances.index');
        Route::get('/admin/ambulances/create', [AmbulanceController::class, 'create'])->name('admin.ambulances.create');
        Route::post('/admin/ambulances', [AmbulanceController::class, 'store'])->name('admin.ambulances.store');
        Route::get('/admin/ambulances/{ambulance}', [AmbulanceController::class, 'show'])->name('admin.ambulances.show');
        Route::get('/admin/ambulances/{ambulance}/edit', [AmbulanceController::class, 'edit'])->name('admin.ambulances.edit');
        Route::put('/admin/ambulances/{ambulance}', [AmbulanceController::class, 'update'])->name('admin.ambulances.update');
        Route::patch('/admin/ambulances/{ambulance}/archive', [AmbulanceController::class, 'archive'])->name('admin.ambulances.archive');
        Route::patch('/admin/ambulances/{ambulance}/restore', [AmbulanceController::class, 'restore'])->name('admin.ambulances.restore');
        Route::post('/admin/ambulances/{ambulance}/fuel-logs', [AmbulanceController::class, 'storeFuelLog'])->name('admin.ambulances.fuel-logs.store');
        Route::post('/admin/ambulances/{ambulance}/maintenance-logs', [AmbulanceController::class, 'storeMaintenanceLog'])->name('admin.ambulances.maintenance-logs.store');
    });

    // S6 — Admin incident intake list (read-only triage; dispatch is S7).
    Route::middleware('can.perm:view-incidents')->group(function () {
        Route::get('/admin/incidents', [IncidentController::class, 'index'])->name('admin.incidents.index');
        Route::get('/admin/incidents/{incident}', [IncidentController::class, 'show'])->name('admin.incidents.show');
    });

    // S7 — DSS + Dispatch.
    Route::middleware('can.perm:dispatch-incidents')->group(function () {
        Route::get('/admin/dispatch', [DispatchController::class, 'index'])->name('admin.dispatch.index');
        Route::get('/admin/dispatch/{incident}', [DispatchController::class, 'show'])->name('admin.dispatch.show');
        Route::post('/admin/dispatch/{incident}', [DispatchController::class, 'store'])->name('admin.dispatch.store');
        Route::patch('/admin/dispatch/assignments/{assignment}/reassign', [DispatchController::class, 'reassign'])->name('admin.dispatch.reassign');
    });

    // S8 — Driver + live tracking.
    Route::middleware('can.perm:drive-unit')->group(function () {
        Route::get('/admin/driver/duty', [DriverController::class, 'duty'])->name('admin.driver.duty');
        Route::patch('/admin/driver/duty', [DriverController::class, 'updateDuty'])->name('admin.driver.duty.update');
        Route::get('/admin/driver/assignments/{assignment}', [DriverController::class, 'assignment'])->name('admin.driver.assignment');
        Route::patch('/admin/driver/assignments/{assignment}/advance', [DriverController::class, 'advance'])->name('admin.driver.advance');
        Route::post('/admin/driver/assignments/{assignment}/location', [DriverController::class, 'pushLocation'])
            ->middleware('throttle:60,1')->name('admin.driver.location');
    });

    // S9 — Medical care (medic).
    Route::middleware('can.perm:record-care')->group(function () {
        Route::get('/admin/incidents/{incident}/care', [CareController::class, 'show'])->name('admin.care.show');
        Route::post('/admin/incidents/{incident}/care/vitals', [CareController::class, 'storeVitals'])->name('admin.care.vitals.store');
        Route::post('/admin/incidents/{incident}/care/treatments', [CareController::class, 'storeTreatment'])->name('admin.care.treatments.store');
        Route::post('/admin/incidents/{incident}/care/notes', [CareController::class, 'storeNote'])->name('admin.care.notes.store');
        Route::put('/admin/incidents/{incident}/care/patient', [CareController::class, 'upsertPatient'])->name('admin.care.patient.upsert');
        Route::patch('/admin/incidents/{incident}/care/resolve', [CareController::class, 'resolveOnScene'])->name('admin.care.resolve');
    });

    // S9 — Hospitals + handoff.
    Route::middleware('can.perm:manage-hospitals')->group(function () {
        Route::get('/admin/hospitals', [HospitalController::class, 'index'])->name('admin.hospitals.index');
        Route::get('/admin/hospitals/create', [HospitalController::class, 'create'])->name('admin.hospitals.create');
        Route::post('/admin/hospitals', [HospitalController::class, 'store'])->name('admin.hospitals.store');
        Route::get('/admin/hospitals/{hospital}', [HospitalController::class, 'show'])->name('admin.hospitals.show');
        Route::post('/admin/incidents/{incident}/endorse', [HospitalController::class, 'endorse'])->name('admin.hospitals.endorse');
        Route::patch('/admin/endorsements/{endorsement}/respond', [HospitalController::class, 'respond'])->name('admin.hospitals.respond');
        Route::patch('/admin/endorsements/{endorsement}/handoff', [HospitalController::class, 'confirmHandoff'])->name('admin.hospitals.handoff');
    });

    // S10 — Anti-abuse / safety + sustainability.
    Route::middleware('can.perm:manage-safety')->group(function () {
        Route::get('/admin/safety', [SafetyController::class, 'index'])->name('admin.safety.index');
        Route::patch('/admin/safety/incidents/{incident}/flag', [SafetyController::class, 'flag'])->name('admin.safety.flag');
        Route::patch('/admin/safety/devices/{device}/block', [SafetyController::class, 'block'])->name('admin.safety.block');
        Route::patch('/admin/safety/devices/{device}/unblock', [SafetyController::class, 'unblock'])->name('admin.safety.unblock');
        Route::get('/admin/ads', [SafetyController::class, 'ads'])->name('admin.ads.index');
        Route::patch('/admin/ads/{ad}/toggle', [SafetyController::class, 'toggleAd'])->name('admin.ads.toggle');
    });

    // S11 — LGU performance reports (read-only).
    Route::middleware('can.perm:view-reports')->group(function () {
        Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    });

    // S11 — Personal notifications (every signed-in user; no extra permission).
    Route::get('/admin/notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::patch('/admin/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('admin.notifications.read-all');
    Route::patch('/admin/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('admin.notifications.read');
});

// --- S6 — Public citizen / guest request intake (no auth; guests allowed) ---
Route::get('/request', [RequestIntakeController::class, 'create'])->name('request.create');
Route::post('/request', [RequestIntakeController::class, 'store'])
    ->middleware('throttle:10,1')->name('request.store');
Route::get('/request/{code}', [RequestIntakeController::class, 'track'])->name('request.track');
Route::get('/request/{code}/status', [RequestIntakeController::class, 'status'])
    ->middleware('throttle:60,1')->name('request.status');
Route::patch('/request/{code}/cancel', [RequestIntakeController::class, 'cancel'])
    ->middleware('throttle:10,1')->name('request.cancel');

Route::get('/', fn () => redirect()->route('login'));
