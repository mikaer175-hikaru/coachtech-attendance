<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;

class UserRequestController extends Controller
{
    // 申請一覧
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        // タブの正規化（想定外値は pending 扱い）
        $tab = $request->query('tab', 'pending');
        $tab = \in_array($tab, ['pending', 'approved'], true) ? $tab : 'pending';

        // 勤怠IDで絞り込み（勤怠一覧→申請一覧の導線用）
        $attendanceId = $request->integer('attendance_id');

        // ベースクエリ（本人のみ）
        $base = \App\Models\StampCorrectionRequest::ownedBy($userId)
            ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId))
            ->with(['attendance:id,work_date']);

        // 承認待ち
        $pending = (clone $base)->pending()
            ->latest()
            ->paginate(10, ['*'], 'pending_page')
            ->appends($request->only(['tab', 'attendance_id', 'approved_page']));

        // 承認済み
        $approved = (clone $base)->approved()
            ->latest()
            ->paginate(10, ['*'], 'approved_page')
            ->appends($request->only(['tab', 'attendance_id', 'pending_page']));

        return view('requests.index', compact('tab', 'pending', 'approved', 'attendanceId'));
    }

    // 申請詳細
    public function show(Request $http, StampCorrectionRequest $requestModel)
    {
        // 自分の申請のみ閲覧可
        if ($requestModel->user_id !== $http->user()->id) {
            abort(403);
        }

        // 勤怠に紐づかない場合は一覧へ
        if (!$requestModel->attendance_id) {
            return redirect()->route('requests.index')
                ->with('error', 'この申請には勤怠情報が紐づいていません。');
        }

        // 勤怠詳細へ転送（あなたのルート名に合わせる）
        return redirect()->route('attendance.show', $requestModel->attendance_id);
    }
}
