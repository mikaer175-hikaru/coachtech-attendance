<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest as AttendanceCorrectionFormRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest as ACR;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StampCorrectionRequestController extends Controller
{
    // 申請一覧（一般ユーザー）
    public function index(Request $request)
    {
        $tab = $request->query('tab');
        if (!in_array($tab, ['pending', 'approved'], true)) {
            $tab = $request->has('approved_page') ? 'approved' : 'pending';
        }

        $pending = ACR::with(['attendance:id,work_date','attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', ACR::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $approved = ACR::with(['attendance:id,work_date','attendance.user:id,name'])
            ->where('user_id', Auth::id())
            ->where('status', ACR::STATUS_APPROVED)
            ->orderByDesc('approved_at')
            ->paginate(10, ['*'], 'approved_page')
            ->withQueryString();

        return view('requests.index', compact('tab', 'pending', 'approved'));
    }

    // 申請“詳細” → 勤怠詳細へリダイレクト
    public function show(ACR $stamp_request)
    {
        return redirect()->route('attendance.show', $stamp_request->attendance_id);
    }

    // 修正申請の保存（勤怠詳細からの POST）
    public function store(AttendanceCorrectionFormRequest $request, Attendance $attendance)
    {
        // 本人以外は 403
        abort_if((int) $attendance->user_id !== (int) Auth::id(), 403);

        // 同一勤怠の PENDING が既にあるなら back
        $exists = ACR::where('attendance_id', $attendance->id)
            ->where('user_id', Auth::id())
            ->where('status', ACR::STATUS_PENDING)
            ->exists();

        if ($exists) {
            return back()
                ->with('error', 'この勤怠には承認待ちの修正申請が既に存在します。')
                ->withInput();
        }

        $workDate = $attendance->work_date instanceof Carbon
            ? $attendance->work_date->copy()
            : Carbon::parse($attendance->work_date);

        $toDT = static function (?string $hm) use ($workDate) {
            if (!$hm) return null;
            [$h, $m] = array_pad(explode(':', $hm), 2, 0);
            return $workDate->copy()->setTime((int)$h, (int)$m);
        };

        DB::transaction(function () use ($request, $attendance, $toDT) {
            ACR::create([
                'attendance_id'  => $attendance->id,
                'user_id'        => Auth::id(),
                'new_start_time' => $toDT($request->input('start_time')),
                'new_end_time'   => $toDT($request->input('end_time')),
                'new_breaks'     => $request->input('breaks', []),
                'note'           => (string) $request->input('note'),
                'status'         => ACR::STATUS_PENDING,
            ]);

            // 勤怠側を承認待ちに
            $attendance->update(['status' => 'pending']);
        });

        return redirect()
            ->route('stamp_requests.index')
            ->with('success', '修正申請を送信しました。承認待ちです。');
    }
}
