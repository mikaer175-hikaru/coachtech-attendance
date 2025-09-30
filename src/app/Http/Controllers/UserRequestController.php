<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\AttendanceCorrectRequest as ACR;

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
            $pending = ACR::with(['attendance:id,work_date,user_id', 'attendance.user:id,name'])
                ->pending()
                ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
                ->latest() // created_at desc
                ->paginate(10, ['*'], 'pending_page');

            $approved = ACR::with(['attendance:id,work_date,user_id', 'attendance.user:id,name'])
                ->approved()
                ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
                ->latestApproved() // approved_at desc
                ->paginate(10, ['*'], 'approved_page');

            return view('admin.stamp_requests.index', compact('tab', 'pending', 'approved', 'attendanceId'));
        }

        // ============== 一般ユーザー：本人のみ ==============
        $userId = $request->user()->id;

        $pending = ACR::with(['attendance:id,work_date'])
            ->ownedBy($userId)
            ->pending()
            ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
            ->latest()
            ->paginate(10, ['*'], 'pending_page');

        $approved = ACR::with(['attendance:id,work_date'])
            ->ownedBy($userId)
            ->approved()
            ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
            ->latestApproved()
            ->paginate(10, ['*'], 'approved_page');

        return view('requests.index', compact('tab', 'pending', 'approved', 'attendanceId'));
    }

    // 申請詳細（一般ユーザーのみ使用：勤怠詳細へ転送）
    public function show(Request $http, ACR $requestModel)
    {
        // 自分の申請のみ閲覧可（アーリーリターン）
        if ($requestModel->user_id !== $http->user()->id) {
            abort(403);
        }

        if (!$requestModel->attendance_id) {
            return redirect()->route('stamp_requests.index')
                ->with('error', 'この申請には勤怠情報が紐づいていません。');
        }

        // 勤怠詳細へ転送（一般ユーザーの詳細ルート名に合わせる）
        return redirect()->route('attendance.show', $requestModel->attendance_id);
    }
}

