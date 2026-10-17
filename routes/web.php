<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Director\CollegeController;
use App\Http\Controllers\Director\CounselorController;
use App\Http\Controllers\Director\ExportController;
use App\Http\Controllers\Director\ProgramController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\EntranceTestResultController;
use App\Http\Controllers\Staff\NotificationController;
use App\Http\Controllers\Staff\RecordController;
use App\Http\Controllers\Staff\TrackerController;
use App\Http\Controllers\Student\CumulativeRecordController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Support\Form5;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Generic entry point (used by Breeze redirects): sends each user to their role's dashboard.
 */
Route::get('/dashboard', function (Request $request) {
    return redirect()->route($request->user()->dashboardRoute());
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return view('student.dashboard', ['record' => $request->user()->latestCumulativeRecord]);
    })->name('dashboard');

    // Form 5: Student Cumulative Record wizard
    Route::get('/record', [CumulativeRecordController::class, 'index'])->name('record.index');
    Route::get('/record/start', [CumulativeRecordController::class, 'create'])->name('record.create');
    Route::post('/record', [CumulativeRecordController::class, 'store'])->name('record.store');
    Route::get('/records/{record}', [CumulativeRecordController::class, 'show'])->name('record.show');
    Route::get('/records/{record}/photo', [CumulativeRecordController::class, 'photo'])->name('record.photo');
    Route::get('/records/{record}/steps/{step}', [CumulativeRecordController::class, 'edit'])
        ->whereIn('step', array_keys(Form5::STEPS))
        ->name('record.edit');
    Route::put('/records/{record}/steps/{step}', [CumulativeRecordController::class, 'update'])
        ->whereIn('step', array_keys(Form5::STEPS))
        ->name('record.update');
    Route::post('/records/{record}/submit', [CumulativeRecordController::class, 'submit'])->name('record.submit');

    // Student Notifications
    Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [StudentNotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}', [StudentNotificationController::class, 'open'])->name('notifications.open');
});

Route::middleware(['auth', 'verified', 'role:counselor'])->prefix('counselor')->name('counselor.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:director'])->prefix('director')->name('director.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('counselors', CounselorController::class)->except(['show', 'destroy']);
    Route::patch('/counselors/{counselor}/deactivate', [CounselorController::class, 'deactivate'])->name('counselors.deactivate');
    Route::patch('/counselors/{counselor}/reactivate', [CounselorController::class, 'reactivate'])->name('counselors.reactivate');

    Route::resource('colleges', CollegeController::class)->except(['show']);

    Route::get('/export/analysis', [ExportController::class, 'analysis'])->name('export.analysis');
    Route::get('/export/logs', [ExportController::class, 'logs'])->name('export.logs');
    Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
    Route::get('/programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit');
    Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');
});

/*
 * Staff side (counselors: assigned colleges only; director: all colleges).
 */
Route::middleware(['auth', 'verified', 'role:counselor,director'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/records', [RecordController::class, 'index'])->name('records.index');
    Route::get('/records/export', [RecordController::class, 'export'])->name('records.export');
    Route::get('/records/{record}', [RecordController::class, 'show'])->name('records.show');
    Route::get('/records/{record}/photo', [RecordController::class, 'photo'])->name('records.photo');
    Route::post('/records/{record}/review', [RecordController::class, 'review'])->name('records.review');

    // SATA entrance test results (never exposed to students)
    Route::get('/students/{student}/sata', [EntranceTestResultController::class, 'index'])->name('sata.index');
    Route::get('/students/{student}/sata/create', [EntranceTestResultController::class, 'create'])->name('sata.create');
    Route::post('/students/{student}/sata', [EntranceTestResultController::class, 'store'])->name('sata.store');
    Route::get('/sata/{result}', [EntranceTestResultController::class, 'show'])->name('sata.show');
    Route::get('/sata/{result}/edit', [EntranceTestResultController::class, 'edit'])->name('sata.edit');
    Route::put('/sata/{result}', [EntranceTestResultController::class, 'update'])->name('sata.update');

    Route::get('/tracker', TrackerController::class)->name('tracker');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
});

// Register broadcasting authentication route (needed for Echo/Reverb)
Broadcast::routes(['middleware' => ['web', 'auth']]);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified', 'role:student,counselor,director'])->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/export', [AppointmentController::class, 'export'])->name('appointments.export');
    Route::post('/appointments', [AppointmentController::class, 'store'])
        ->middleware('role:student')
        ->name('appointments.store');
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');

    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
        ->middleware('role:counselor,director')
        ->name('appointments.status');
});

require __DIR__.'/auth.php';
