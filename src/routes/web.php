<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserRequestController;
use App\Http\Controllers\StampCorrectionRequestController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StampCorrectionApproveController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;

// ====================
// ▼ 会員登録・ログイン（一般ユーザー）
// ====================

// トップは未ログインならログインへ
Route::get('/', function () {
    return redirect()->route('login');
});

// 会員登録
Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

// メール認証関連
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->name('verification.notice');

// 署名付きリンクの着地
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance.create');
})->middleware(['auth','signed','throttle:6,1'])->name('verification.verify');

// 認証メール再送
Route::post('/email/verification-notification', function () {
    request()->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth','throttle:6,1'])->name('verification.send');

// ログイン/ログアウト
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

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
    // 勤怠一覧
    Route::get('/attendance/list', [AttendanceController::class, 'index'])
        ->name('attendance.list');

    // 勤怠詳細（暗黙モデルバインディング）
    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])
        ->whereNumber('attendance')
        ->name('attendance.show');

    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])
        ->whereNumber('attendance')
        ->name('attendance.update');

    // 打刻系
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance/start', [AttendanceController::class, 'startWork'])->name('attendance.start');
    Route::post('/attendance/end', [AttendanceController::class, 'endWork'])->name('attendance.end');
    Route::post('/attendance/break-start', [AttendanceController::class, 'startBreak'])->name('attendance.break.start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'endBreak'])->name('attendance.break.end');
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

Route::middleware(['auth:admin', 'verified', 'can:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // 勤怠一覧（管理者）
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
            ->name('attendance.list');

        // 勤怠詳細（管理者）
        Route::get('/attendance/{attendance}', [AdminAttendanceController::class, 'show'])
            ->whereNumber('attendance')
            ->name('attendance.show');

        Route::match(['patch', 'put', 'post'], '/attendance/{attendance}', [AdminAttendanceController::class, 'update'])
            ->whereNumber('attendance')
            ->name('attendance.update');

        // スタッフ一覧 (管理者)
        Route::get('/staff/list', [StaffController::class, 'index'])
            ->name('staff.list');

        // 月次一覧（スタッフ別）
        Route::get('/attendance/staff/{id}', [\App\Http\Controllers\Admin\AttendanceController::class, 'indexMonthly'])
            ->whereNumber('id')
            ->name('attendance.staff.index');

        // CSV 出力
        Route::get('/attendance/staff/{id}/csv', [\App\Http\Controllers\Admin\AttendanceController::class, 'exportMonthlyCsv'])
            ->whereNumber('id')
            ->name('attendance.staff.csv');
    });

// ====================
// ▼ 一般ユーザー申請（一覧は共通エントリでロール出し分け）
// ====================

Route::middleware(['auth', 'verified'])->group(function () {
    // 一覧（管理者／一般ユーザー共用）
    Route::get('/stamp-requests', [UserRequestController::class, 'index'])
        ->name('stamp_requests.index');

    // 申請詳細（共通入口）：ここで管理/一般を分岐してリダイレクト
    Route::get('/stamp-requests/{stamp_request}', [UserRequestController::class, 'show'])
        ->whereNumber('stamp_request')
        ->name('stamp_requests.show');

    // POST 作成
    Route::post('/stamp-requests/{attendance}', [StampCorrectionRequestController::class, 'store'])
        ->whereNumber('attendance')
        ->name('stamp_requests.store');
});

// ====================
// ▼ 管理者：申請詳細/承認/却下（専用パス）
// ====================

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'verified', 'can:admin'])->group(function () {
    Route::get('/stamp-requests/{stamp_request}', [StampCorrectionApproveController::class, 'show'])
        ->whereNumber('stamp_request')
        ->name('stamp_requests.show');

    Route::post('/stamp-requests/{stamp_request}/approve', [StampCorrectionApproveController::class, 'approve'])
        ->whereNumber('stamp_request')
        ->name('stamp_requests.approve');

    Route::post('/stamp-requests/{stamp_request}/reject', [StampCorrectionApproveController::class, 'reject'])
        ->whereNumber('stamp_request')
        ->name('stamp_requests.reject');
});