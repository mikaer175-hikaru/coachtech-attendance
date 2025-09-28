<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\AttendanceCorrectRequest;

class UserRequestController extends Controller
{
    // 申請一覧（一般／管理者 共通）
    public function index(Request $request)
    {
        // タブの正規化（想定外値は pending 扱い）
        $tab = $request->query('tab', 'pending');
        $tab = \in_array($tab, ['pending', 'approved'], true) ? $tab : 'pending';

        // 勤怠IDで絞り込み（勤怠一覧→申請一覧の導線用）
        $attendanceId = $request->integer('attendance_id');

        // ============== 管理者：全ユーザー分 ==============
        if (Gate::allows('admin')) {
            $base = AttendanceCorrectRequest::query()
                ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
                ->with([
                    // 名前表示のため user を attendance 経由で取得
                    'attendance:id,work_date,user_id',
                    'attendance.user:id,name',
                ]);

            $pending = (clone $base)
                ->pending()
                ->orderByDesc('created_at')
                ->paginate(15, ['*'], 'pending_page')
                ->appends($request->only(['tab', 'attendance_id', 'approved_page']));

            $approved = (clone $base)
                ->approved()
                ->orderByDesc('approved_at')
                ->paginate(15, ['*'], 'approved_page')
                ->appends($request->only(['tab', 'attendance_id', 'pending_page']));

            // 管理者用ビューへ
            return view('admin.stamp_requests.index', compact('tab', 'pending', 'approved', 'attendanceId'));
        }

        // ============== 一般ユーザー：本人のみ ==============
        $userId = $request->user()->id;

        $base = AttendanceCorrectRequest::ownedBy($userId)
            ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
            ->with(['attendance:id,work_date']);

        $pending = (clone $base)
            ->pending()
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'pending_page')
            ->appends($request->only(['tab', 'attendance_id', 'approved_page']));

        $approved = (clone $base)
            ->approved()
            ->orderByDesc('approved_at')
            ->paginate(10, ['*'], 'approved_page')
            ->appends($request->only(['tab', 'attendance_id', 'pending_page']));

        return view('requests.index', compact('tab', 'pending', 'approved', 'attendanceId'));
    }

    // 申請詳細（一般ユーザーのみ使用）
    public function show(Request $http, AttendanceCorrectRequest $requestModel)
    {
        // 自分の申請のみ閲覧可（アーリーリターン）
        if ($requestModel->user_id !== $http->user()->id) {
            abort(403);
        }

        if (!$requestModel->attendance_id) {
            return redirect()->route('requests.index')
                ->with('error', 'この申請には勤怠情報が紐づいていません。');
        }

        // 勤怠詳細へ転送（一般ユーザーの詳細ルート名に合わせる）
        return redirect()->route('attendance.show', $requestModel->attendance_id);
    }
}
