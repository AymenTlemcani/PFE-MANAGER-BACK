<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectProposalController;
use App\Http\Controllers\ProjectAssignmentController;
use App\Http\Controllers\StudentPairController;
use App\Http\Controllers\EmailPeriodController;
use App\Http\Controllers\DefenseSessionController;
use App\Http\Controllers\JuryPreferenceController;
use App\Http\Controllers\JuryAssignmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UserImportLogController;
use App\Http\Controllers\EmailPeriodReminderController;
use App\Http\Controllers\EmailPeriodTemplateController;

// Public routes - removed /api prefix since it's in the URL
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/validate-reset-token', [AuthController::class, 'validateResetToken']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User management routes (protected by controller)
    Route::apiResource('users', UserController::class);
    Route::post('/users/import', [UserController::class, 'importUsers']);
    Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete']);
    
    // Administrator routes
    Route::apiResource('administrators', AdministratorController::class);
    Route::post('/email-periods', [AdministratorController::class, 'createEmailPeriod']);
    Route::post('/defense-sessions/plan', [AdministratorController::class, 'planDefenseSessions']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/import-logs', [UserImportLogController::class, 'index']);

    // Teacher routes
    Route::apiResource('teachers', TeacherController::class);
    Route::post('/projects/validate/{projectId}', [TeacherController::class, 'validateProject'])
        ->middleware(['can:validate-projects', 'check.responsible', 'check.project.assignment']);
    Route::post('/projects/supervise', [TeacherController::class, 'selectProjectsForSupervision']);
    Route::post('/projects/propose', [ProjectController::class, 'store'])
        ->middleware('check.deadline:teacher_proposal_period');
    Route::post('/jury-preferences', [TeacherController::class, 'submitJuryPreferences'])
        ->middleware('check.deadline:jury_preference_period');

    // Student routes
    Route::apiResource('students', StudentController::class);
    Route::apiResource('student-pairs', StudentPairController::class);
    Route::post('/projects/propose', [StudentController::class, 'proposeProject'])
        ->middleware('check.deadline:student_proposal_period');
    Route::post('/project-choices', [StudentController::class, 'submitProjectChoices'])
        ->middleware('check.deadline:project_choice_period');
    Route::post('/student-pairs', [StudentPairController::class, 'store'])
        ->middleware('check.pair');

    // Company routes
    Route::apiResource('companies', CompanyController::class);
    Route::post('/projects/propose', [CompanyController::class, 'proposeProject'])
        ->middleware('check.deadline:company_proposal_period');
    Route::get('/projects/proposed', [CompanyController::class, 'getProposedProjects']);

    // Project management
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('project-proposals', ProjectProposalController::class);
    Route::apiResource('project-assignments', ProjectAssignmentController::class);

    // Defense and jury management
    Route::apiResource('defense-sessions', DefenseSessionController::class);
    Route::apiResource('jury-preferences', JuryPreferenceController::class);
    Route::apiResource('jury-assignments', JuryAssignmentController::class);
    Route::post('/jury-assignments/auto', [JuryAssignmentController::class, 'autoAssign']);

    // Email periods and notifications
    Route::apiResource('email-periods', EmailPeriodController::class);
    Route::apiResource('email-period-templates', EmailPeriodTemplateController::class);
    Route::apiResource('email-period-reminders', EmailPeriodReminderController::class);
    Route::post('/email-period-reminders/send', [EmailPeriodReminderController::class, 'sendReminders']);
    Route::apiResource('notifications', NotificationController::class);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});