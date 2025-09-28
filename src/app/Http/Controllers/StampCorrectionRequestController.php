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
        $tab = $request->query('tab');
        if (!in_array($tab, ['pending', 'approved'], true)) {
            $tab = $request->has('approved_page') ? 'approved' : 'pending';
        }

        $pending = AttendanceCorrectRequest::with(['attendance:id,work_date','attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $approved = AttendanceCorrectRequest::with(['attendance:id,work_date','attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', AttendanceCorrectRequest::STATUS_APPROVED)
            ->orderByDesc('approved_at')
            ->paginate(10, ['*'], 'approved_page')
            ->withQueryString();

        return view('requests.index', compact('tab', 'pending', 'approved'));
    }

    // 申請“詳細” → 勤怠詳細へリダイレクト（テスト仕様）
    public function show(AttendanceCorrectRequest $stamp_request)
    {
        abort_if($stamp_request->user_id !== Auth::id(), 403);

        return redirect()->route('attendance.show', $stamp_request->attendance_id);
    }

    // 修正申請の保存（勤怠詳細からの POST）
    public function store(CorrectionRequest $request)
    {
        // ★ ルートに {attendance} は無い。body の attendance_id から取得する
        $attendanceId = (int) $request->input('attendance_id');
        $attendance   = Attendance::findOrFail($attendanceId);

        abort_if($attendance->user_id !== Auth::id(), 403);

        $workDate = $attendance->work_date instanceof Carbon
            ? $attendance->work_date->copy()
            : Carbon::parse($attendance->work_date);

        $toDateTime = static function (?string $hm) use ($workDate) {
            if (!$hm) {
                return null;
            }
            [$h, $m] = array_pad(explode(':', $hm), 2, 0);
            return $workDate->copy()->setTime((int) $h, (int) $m);
        };

        // 同一勤怠の pending があればバリデーションエラー扱い
        $existsPending = AttendanceCorrectRequest::query()
            ->where('attendance_id', $attendanceId)
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->exists();

        if ($existsPending) {
            return back()
                ->with('error', 'この勤怠には承認待ちの修正申請が既に存在します。')
                ->withInput();
        }

        DB::transaction(function () use ($request, $attendance, $toDateTime) {
            AttendanceCorrectRequest::create([
                'attendance_id'  => $attendance->id,
                'user_id'        => Auth::id(),
                'new_start_time' => $toDateTime($request->input('start_time')),
                'new_end_time'   => $toDateTime($request->input('end_time')),
                'new_breaks'     => $request->input('breaks', []), // casts: array/json
                'note'           => (string) $request->input('note'),
                'status'         => AttendanceCorrectRequest::STATUS_PENDING,
            ]);

            // 勤怠を承認待ち状態に
            $attendance->update(['status' => 'pending']);
        });

        // 一覧へ（テスト親和）
        return redirect()
            ->route('stamp_requests.index')
            ->with('success', '修正申請を送信しました。承認待ちです。');
    }
}
