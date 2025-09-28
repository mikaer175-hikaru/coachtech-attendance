<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use App\Http\Requests\AttendanceCorrectionRequest;

class StampCorrectionRequestController extends Controller
{
    // 申請一覧（自分の pending/approved をタブ別・別ページネーション）
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $tab = $request->query('tab', 'pending');

        $pending = AttendanceCorrectRequest::where('user_id', $userId)
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->with(['attendance'])
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $approved = AttendanceCorrectRequest::where('user_id', $userId)
            ->where('status', AttendanceCorrectRequest::STATUS_APPROVED)
            ->with(['attendance'])
            ->orderByDesc('approved_at')
            ->paginate(10, ['*'], 'approved_page')
            ->withQueryString();

        return view('requests.index', compact('tab', 'pending', 'approved'));
    }

    // “申請詳細”は存在せず、勤怠詳細へリダイレクト（自分の申請のみ可）
    public function show(AttendanceCorrectRequest $stamp_request)
    {
        abort_if($stamp_request->user_id !== Auth::id(), 403);

        return redirect()->route('attendance.show', $stamp_request->attendance_id);
    }

    // 申請作成：note 必須、同一勤怠に pending があれば back()->with('error')、他人勤怠は 403
    public function store(AttendanceCorrectionRequest $request, int $attendance)
    {
        // ★ モデルバインディングを使わず、ID から明示取得（グローバルスコープ無視で確実に拾う）
        $attendance = Attendance::withoutGlobalScopes()->findOrFail($attendance);

        // 本人以外なら 403
        abort_if((int)$attendance->user_id !== (int)Auth::id(), 403);

        // 同一勤怠の pending があればエラー（テストは session('error') を期待）
        $exists = AttendanceCorrectRequest::where('attendance_id', $attendance->id)
            ->where('status', AttendanceCorrectRequest::STATUS_PENDING)
            ->exists();

        if ($exists) {
            return back()
                ->with('error', 'この勤怠には承認待ちの修正申請が既に存在します。')
                ->withInput();
        }

        // 勤務日を基準に HH:MM → DateTime
        $workDate = $attendance->work_date instanceof Carbon
            ? $attendance->work_date->copy()
            : Carbon::parse($attendance->work_date);

        $toDT = static function (?string $hm) use ($workDate) {
            if (!$hm) return null;
            [$h, $m] = array_pad(explode(':', $hm), 2, 0);
            return $workDate->copy()->setTime((int)$h, (int)$m);
        };

        DB::transaction(function () use ($request, $attendance, $toDT) {
            AttendanceCorrectRequest::create([
                'attendance_id'  => $attendance->id,
                'user_id'        => Auth::id(),
                'new_start_time' => $toDT($request->input('start_time')),
                'new_end_time'   => $toDT($request->input('end_time')),
                'new_breaks'     => $request->input('breaks', []), // casts: array/json
                'note'           => (string)$request->input('note'),
                'status'         => AttendanceCorrectRequest::STATUS_PENDING,
            ]);

            // 対象勤怠を承認待ちへ
            $attendance->update(['status' => 'pending']);
        });

        // 一覧へ 302
        return redirect()
            ->route('stamp_requests.index')
            ->with('success', '修正申請を送信しました。承認待ちです。');
    }
}
