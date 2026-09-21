<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Public routes ──────────────────────────────────────────────────────────
Route::get('/', [ResearchController::class, 'index'])->name('home');
Route::get('/about', function () { return view('pages.about'); })->name('about');
Route::get('/faq', function () { return view('pages.faq'); })->name('faq');
Route::get('/contact', function () { return view('pages.contact'); })->name('contact');
Route::get('/policy', [PolicyController::class, 'show'])->name('policy.show');
Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'name'    => 'required|string|max:255',
        'email'   => 'required|email|max:255',
        'subject' => 'nullable|string|max:255',
        'message' => 'required|string',
    ]);
    \App\Models\Message::create([
        'name'    => $request->name,
        'email'   => $request->email,
        'subject' => $request->subject,
        'message' => $request->message,
    ]);
    return back()->with('contact_success', true);
})->name('contact.send');
Route::get('/research/{research}', [ResearchController::class, 'show'])->name('research.show');
Route::post('/research/{research}/citation-copy', [ResearchController::class, 'recordCitationCopy'])->name('research.citation-copy');
Route::get('/department/{department}', [ResearchController::class, 'byDepartment'])->name('research.department')->where('department', '[^/]+');
Route::get('/department/{department}/{course}', [ResearchController::class, 'byCourse'])->name('research.course')->where(['department' => '[^/]+', 'course' => '[^/]+']);

// ── Auth routes ────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/policy/accept', [PolicyController::class, 'accept'])->middleware('auth')->name('policy.accept');
Route::get('/research/{research}/protected-view', [ResearchController::class, 'protectedView'])->middleware('policy.accepted')->name('research.protectedView');
Route::get('/research/{research}/stream-pdf', [ResearchController::class, 'streamPdf'])->middleware('policy.accepted')->name('research.streamPdf');
Route::get('/research/{research}/page-image/{page}', [ResearchController::class, 'streamPageImage'])->name('research.streamPageImage');
Route::get('/research/{research}/view-file', [ResearchController::class, 'viewFile'])->middleware('policy.accepted')->name('research.view-file');
Route::post('/research/{research}/capture-attempt', [ResearchController::class, 'logCaptureAttempt'])->middleware('policy.accepted')->name('research.capture-attempt');

// ── Authenticated user routes ──────────────────────────────────────────────
Route::middleware(['auth', 'policy.accepted'])->group(function () {
    Route::get('/dashboard', [ResearchController::class, 'userDashboard'])->name('user.dashboard');
    Route::get('/submit', [ResearchController::class, 'submitForm'])->name('research.submit');
    Route::post('/submit', [ResearchController::class, 'store'])->name('research.store');
    Route::get('/my-submissions', [ResearchController::class, 'mySubmissions'])->name('research.my-submissions');
    Route::get('/pinned', [ResearchController::class, 'pinned'])->name('research.pinned');
    Route::post('/pin/{research}', [ResearchController::class, 'pinToggle'])->name('research.pin');

     // ✅ ADD MO ITO
    Route::get('/research/{research}/admin-view', [ResearchController::class, 'adminViewFile'])->name('research.adminViewFile');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::get('/profile/photo/remove', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');

});

// ── Admin routes ───────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'policy.accepted', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Semester management
    Route::get('/semesters', [AdminController::class, 'semesters'])->name('semesters');
    Route::post('/semesters', [AdminController::class, 'storeSemester'])->name('semesters.store');
    Route::get('/semesters/{semester}', [AdminController::class, 'showSemester'])->name('semesters.show');
    Route::patch('/semesters/{semester}', [AdminController::class, 'updateSemester'])->name('semesters.update');
    Route::post('/semesters/{semester}/archive', [AdminController::class, 'archiveSemester'])->name('semesters.archive');
    Route::delete('/semesters/{semester}', [AdminController::class, 'destroySemester'])->name('semesters.destroy');

    // Research management
    Route::get('/researches', [AdminController::class, 'researches'])->name('researches');
    Route::get('/researches/{research}', [AdminController::class, 'showResearch'])->name('research.show');
    Route::get('/researches/{research}/view-file', [ResearchController::class, 'adminViewFile'])->name('research.view-file');
    Route::post('/researches/{research}/approve', [AdminController::class, 'approveResearch'])->name('research.approve');
    Route::post('/researches/{research}/archive', [AdminController::class, 'archiveResearch'])->name('research.archive');
    Route::post('/researches/{research}/publish', [AdminController::class, 'publishResearch'])->name('research.publish');
    Route::post('/researches/{research}/reject', [AdminController::class, 'rejectResearch'])->name('research.reject');
    Route::delete('/researches/{research}', [AdminController::class, 'deleteResearch'])->name('research.delete');

    // User management
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('user.show');
    Route::post('/users/{user}/toggle', [AdminController::class, 'toggleUserStatus'])->name('user.toggle');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('user.delete');

    // Messages
    Route::get('/messages', [AdminController::class, 'messages'])->name('messages');
    Route::get('/messages/{message}', [AdminController::class, 'showMessage'])->name('message.show');
    Route::delete('/messages/{message}', [AdminController::class, 'deleteMessage'])->name('message.delete');
    Route::post('/messages/mark-all-read', [AdminController::class, 'markAllRead'])->name('messages.mark-read');

    // Add research
    Route::get('/add-research', [AdminController::class, 'addResearch'])->name('add-research');
    Route::post('/add-research', [AdminController::class, 'storeResearch'])->name('store-research');

    // Reports
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');

    // Capture logs
    Route::get('/capture-attempt-logs', [AdminController::class, 'captureAttemptLogs'])->name('capture-attempt-logs');
    Route::get('/capture-attempt-logs/summary', [AdminController::class, 'captureAttemptSummary'])->name('capture-attempt-logs.summary');
    Route::post('/capture-attempt-logs/mark-viewed', [AdminController::class, 'markCaptureActivityViewed'])->name('capture-attempt-logs.mark-viewed');

    // Admin management (legacy)
    Route::get('/create-admin', [AdminController::class, 'createAdmin'])->name('create-admin');
    Route::post('/create-admin', [AdminController::class, 'storeAdmin'])->name('store-admin');
    Route::get('/create-user', [AdminController::class, 'createUser'])->name('create-user');
    Route::post('/create-user', [AdminController::class, 'storeUser'])->name('store-user');
    Route::post('/import-users', [AdminController::class, 'importUsers'])->name('import-users');

    // Researcher accounts
    Route::get('/researcher-accounts', [AdminController::class, 'researcherAccounts'])->name('researcher-accounts');
});

// ── Owner routes ─────────────────────────────────────────────────────
