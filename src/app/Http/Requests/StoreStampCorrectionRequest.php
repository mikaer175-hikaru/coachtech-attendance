<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use App\Http\Requests\AttendanceCorrectionRequest;

class StampCorrectionRequestController extends Controller
{
    // 申請一覧（一般）
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $tab = $request->query('tab', 'pending');

        $pending = AttendanceCorrectRequest::with(['attendance'])
            ->where('user_id', $userId)
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->latest()
            ->paginate(10, ['*'], 'pending_page');

        $approved = AttendanceCorrectRequest::with(['attendance'])
            ->where('user_id', $userId)
            ->where('status', AttendanceCorrectRequest::STATUS_APPROVED)
            ->orderByDesc('approved_at')
            ->paginate(10, ['*'], 'approved_page');

        return view('requests.index', compact('tab', 'pending', 'approved'));
    }

    // 申請“詳細”は存在せず、勤怠詳細へリダイレクト
    public function show(AttendanceCorrectRequest $stamp_request)
    {
        abort_if($stamp_request->user_id !== Auth::id(), 403);

        return redirect()->route('attendance.show', $stamp_request->attendance_id);
    }

    // POST作成
    public function store(AttendanceCorrectionRequest $request, int $attendance)
    {
        $attendance = Attendance::findOrFail($attendance);
        abort_if((int)$attendance->user_id !== (int)Auth::id(), 403);

        $exists = AttendanceCorrectRequest::where('attendance_id', $attendance->id)
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->exists();

        if ($exists) {
            return back()->with('error', 'この勤怠には承認待ちの修正申請が既に存在します。')->withInput();
        }

        $workDate = $attendance->work_date instanceof Carbon
            ? $attendance->work_date->copy()
            : Carbon::parse($attendance->work_date);

        $toDT = static function (?string $hm) use ($workDate) {
            if (!$hm) return null;
            [$h,$m] = array_pad(explode(':', $hm), 2, 0);
            return $workDate->copy()->setTime((int)$h,(int)$m);
        };

        DB::transaction(function () use ($request, $attendance, $toDT) {
            AttendanceCorrectRequest::create([
                'attendance_id'  => $attendance->id,
                'user_id'        => Auth::id(),
                'new_start_time' => $toDT($request->input('start_time')),
                'new_end_time'   => $toDT($request->input('end_time')),
                'new_breaks'     => $request->input('breaks', []),
                'note'           => (string) $request->input('note'),
                'status'         => AttendanceCorrectRequest::STATUS_PENDING,
            ]);
            $attendance->update(['status' => 'pending']);
        });

        return redirect()->route('stamp_requests.index')
            ->with('success', '修正申請を送信しました。承認待ちです。');
    }
}
