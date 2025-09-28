<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserRequestController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StampCorrectionApproveController;
use App\Http\Controllers\Admin\StampCorrectionListController as AdminList;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;

// ====================
// ▼ 会員登録・ログイン（一般ユーザー）
// ====================

// ExampleTest 用（トップが 200）
Route::get('/', fn () => response('OK', 200));

// 会員登録
Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->name('verification.notice');

// 署名付きリンクの着地（パスはそのまま）
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/attendance');
})->middleware('signed')->name('verification.verify');

// ログイン
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

// ログアウト
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// ====================
// ▼ 一般ユーザー用ページ（初回設定が完了している場合のみ）
// ====================

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance/start', [AttendanceController::class, 'startWork'])->name('attendance.start');
    Route::post('/attendance/end', [AttendanceController::class, 'endWork'])->name('attendance.end');
    Route::post('/attendance/break-start', [AttendanceController::class, 'startBreak'])->name('attendance.break.start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'endBreak'])->name('attendance.break.end');
});

// 認証済みユーザー（初回チェック不要）でも見られるページ
Route::middleware(['auth', 'verified'])->group(function () {
    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'index'])
        ->name('attendance.list');

    // 勤怠詳細（暗黙モデルバインディング）
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])
        ->whereNumber('attendance')
        ->name('attendance.show');
});

// ====================
// ▼ 管理者ログイン
// ====================

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
});

// ====================
// ▼ 管理者専用ルート
// ====================

Route::middleware(['auth', 'verified', 'can:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // 勤怠一覧（管理者）
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
            ->name('attendance.list');

        // 勤怠詳細（管理者）
        Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])
            ->name('attendance.show');
        Route::match(['patch', 'put', 'post'], '/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');

        // スタッフ一覧 (管理者)
        Route::get('/staff/list', [StaffController::class, 'index'])
            ->name('staff.list');

        // 月次一覧（スタッフ別）
        Route::get('/attendance/staff/{id}', [StaffAttendanceController::class, 'index'])
            ->name('attendance.staff.index');

        // CSV 出力
        Route::get('/attendance/staff/{id}/csv', [StaffAttendanceController::class, 'downloadCsv'])
            ->name('attendance.staff.csv');
    });

// ====================
// ▼ 一般ユーザー申請
// ====================

Route::middleware(['auth', 'verified'])->group(function () {
    // 一覧
    Route::get('/stamp-requests', [StampCorrectionRequestController::class, 'index'])
        ->name('stamp_requests.index');

    // “申請詳細”は存在せず、勤怠詳細へリダイレクトする仕様
    Route::get('/stamp-requests/{stamp_request}', [StampCorrectionRequestController::class, 'show'])
        ->name('stamp_requests.show');

    // 作成（テストがこの名前を要求）
    Route::post('/stamp-requests/{attendance}', [StampCorrectionRequestController::class, 'store'])
        ->whereNumber('attendance')
        ->name('stamp_requests.store');
});

// ====================
// ▼ 管理者も共通の申請一覧（入口を共通パスに集約）
// ====================

Route::middleware(['auth','verified'])
    ->get('/stamp_correction_request/list', [UserRequestController::class, 'index'])
    ->name('admin.stamp_requests.index');

// ====================
// ▼ 管理者：申請詳細/承認/却下（専用パスでOK）
// ====================

Route::middleware(['auth', 'verified', 'can:admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/stamp-requests/{correction}', [StampCorrectionApproveController::class, 'show'])
            ->name('stamp_requests.show');

        Route::post('/stamp-requests/{correction}/approve', [StampCorrectionApproveController::class, 'approve'])
            ->name('stamp_requests.approve');

        Route::post('/stamp-requests/{correction}/reject', [StampCorrectionApproveController::class, 'reject'])
            ->name('stamp_requests.reject');
    });
