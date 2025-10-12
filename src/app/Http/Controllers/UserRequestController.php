<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest as ACR;

class UserRequestController extends Controller
{
    // 申請一覧（一般／管理者 共通）
    public function index(Request $request)
    {
        $user     = $request->user();
        $isAdmin  = $user?->can('admin') ?? false;

        // タブの正規化
        $tab = $request->query('tab', 'pending');
        $tab = \in_array($tab, ['pending', 'approved'], true) ? $tab : 'pending';

        // 勤怠IDで絞り込み（任意）
        $attendanceId = (int) $request->query('attendance_id');

        // ============== 管理者：全ユーザー分 ==============
        if ($isAdmin) {
            $base = ACR::with(['attendance:id,work_date,user_id', 'attendance.user:id,name'])
                ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId));

            $pending  = (clone $base)->pending()->latest()->paginate(10, ['*'], 'pending_page');
            $approved = (clone $base)->approved()->latestApproved()->paginate(10, ['*'], 'approved_page');

            return view('admin.stamp_requests.index', compact('tab', 'pending', 'approved', 'attendanceId', 'isAdmin'));
        }

        // ============== 一般ユーザー：本人のみ ==============
        $base = ACR::with(['attendance:id,work_date'])
            ->ownedBy($user->id)
            ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId));

        $pending  = (clone $base)->pending()->latest()->paginate(10, ['*'], 'pending_page');
        $approved = (clone $base)->approved()->latestApproved()->paginate(10, ['*'], 'approved_page');

        return view('requests.index', compact('tab', 'pending', 'approved', 'attendanceId', 'isAdmin'));
    }

    // 申請詳細（一般）
    public function show(Request $http, ACR $stamp_request)
    {
        if ($stamp_request->user_id !== $http->user()->id) {
            abort(403);
        }
        if (!$stamp_request->attendance_id) {
            return redirect()->route('stamp_requests.index')
                ->with('error', 'この申請には勤怠情報が紐づいていません。');
        }
        return redirect()->route('attendance.show', $stamp_request->attendance_id);
    }
}
