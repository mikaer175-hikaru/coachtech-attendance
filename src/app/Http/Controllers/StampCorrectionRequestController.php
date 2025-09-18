<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest as CorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    // 申請一覧（一般）: 承認待ち/承認済みのタブ表示に使う
    public function index()
    {
        $pending = AttendanceCorrectRequest::with(['attendance:id,work_date','attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'pending_page');

        $approved = AttendanceCorrectRequest::with(['attendance:id,work_date','attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'approved_page');

        return view('stamp_requests.index', compact('pending', 'approved'));
    }

    // 修正申請の保存（勤怠詳細からのPOST）
    public function store(CorrectionRequest $request, Attendance $attendance)
    {
        abort_if($attendance->user_id !== Auth::id(), 403);

        // "HH:mm" を当日の DateTime に
        $toDateTime = function (?string $hm) use ($attendance) {
            if (!$hm) return null;
            [$h, $m] = explode(':', $hm);
            return $attendance->work_date->copy()->setTime((int) $h, (int) $m);
        };

        AttendanceCorrectRequest::create([
            'attendance_id'  => $attendance->id,
            'user_id'        => Auth::id(),
            'new_start_time' => $toDateTime($request->input('start_time')),
            'new_end_time'   => $toDateTime($request->input('end_time')),
            'new_breaks'     => $request->input('breaks', []), // casts=array
            'note'           => $request->string('note'),
            'status'         => 'pending',
        ]);

        // 当該勤怠を承認待ちに
        $attendance->update(['status' => 'pending']);

        return back()->with('success', '修正申請を送信しました。承認待ちです。');
    }
}
