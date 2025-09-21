<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest as CorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StampCorrectionRequestController extends Controller
{
    // 申請一覧（一般）
    public function index(Request $request)
    {
        $pending = AttendanceCorrectRequest::with(['attendance:id,work_date', 'attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $approved = AttendanceCorrectRequest::with(['attendance:id,work_date', 'attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', AttendanceCorrectRequest::STATUS_APPROVED)
            ->orderByDesc('approved_at')
            ->paginate(10, ['*'], 'approved_page')
            ->withQueryString();

        return view('stamp_requests.index', compact('pending', 'approved'));
    }

    // 修正申請の保存（勤怠詳細からのPOST）
    public function store(CorrectionRequest $request, Attendance $attendance)
    {
        abort_if($attendance->user_id !== Auth::id(), 403);

        $workDate = $attendance->work_date instanceof Carbon
            ? $attendance->work_date->copy()
            : Carbon::parse($attendance->work_date);

        $toDateTime = function (?string $hm) use ($workDate) {
            if (!$hm) return null;
            [$h, $m] = explode(':', $hm);
            return $workDate->copy()->setTime((int) $h, (int) $m);
        };

        $existsPending = AttendanceCorrectRequest::where('attendance_id', $attendance->id)
            ->where('user_id', Auth::id())
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->exists();

        if ($existsPending) {
            return back()->with('error', 'この勤怠には承認待ちの修正申請が既に存在します。');
        }

        DB::transaction(function () use ($request, $attendance, $toDateTime) {
            AttendanceCorrectRequest::create([
                'attendance_id'  => $attendance->id,
                'user_id'        => Auth::id(),
                'new_start_time' => $toDateTime($request->input('start_time')),
                'new_end_time'   => $toDateTime($request->input('end_time')),
                'new_breaks'     => $request->input('breaks', []), // casts=array/json
                'note'           => (string) $request->input('note'),
                'status'         => AttendanceCorrectRequest::STATUS_PENDING,
            ]);

            // 当該勤怠を承認待ちに
            $attendance->update(['status' => 'pending']);
        });

        return back()->with('success', '修正申請を送信しました。承認待ちです。');
    }
}
