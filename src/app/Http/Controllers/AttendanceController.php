<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest as ACR;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UpdateAttendanceRequest as UserUpdateAttendanceRequest;
use App\Http\Requests\StartWorkRequest;
use App\Http\Requests\EndWorkRequest;
use App\Http\Requests\StartBreakRequest;
use App\Http\Requests\EndBreakRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest as AdminUpdateAttendanceRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    // 勤怠一覧 （ユーザー指定なし）
    public function index(Request $request)
    {
        $user = $request->user();

        // ?month=YYYY-MM（未指定は今月）
        $month = $request->query('month', now()->format('Y-m'));
        $m     = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $start = $m->copy();
        $end   = $m->copy()->endOfMonth();

        // 今月分の勤怠（休憩も一括ロード）
        $attendances = \App\Models\Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($a) {
                return $a->work_date instanceof \Carbon\CarbonInterface
                    ? $a->work_date->toDateString()
                    : (string) $a->work_date;
            });

        // カレンダーの日付を埋めて表示用の行を作る
        $rows = [];
        for ($d = $start->copy(); $d <= $end; $d = $d->addDay()) {
            /** @var \App\Models\Attendance|null $a */
            $a = $attendances[$d->toDateString()] ?? null;

            $rows[] = [
                'date'          => $d->toDateString(),        // 例: 2025-10-10
                'date_label'    => function_exists('intlcal_create_instance')
                    ? $d->isoFormat('MM/DD(ddd)')
                    : $d->format('m/d').'(['.'日月火水木金土'[$d->dayOfWeek].'])', // 例: 10/10(金)
                'start_hm'      => $a?->start_hm ?? '',       // 例: 09:00
                'end_hm'        => $a?->end_hm ?? '',
                'break_hm'      => $a?->break_hm ?? '',       // 例: 1:00
                'worked_hm'     => $a?->worked_hm ?? '',      // 例: 8:00
                'attendance_id' => $a?->id,
            ];
        }

        return view('attendance.list', [
            'rows'         => $rows,
            'currentMonth' => $m->format('Y-m'),
            'prevMonth'    => $m->copy()->subMonth()->format('Y-m'),
            'nextMonth'    => $m->copy()->addMonth()->format('Y-m'),
        ]);
    }

    // 勤怠詳細
    public function show(Attendance $attendance)
    {
        $user = auth()->user();
        abort_unless($user && $attendance->user_id === $user->id, 403);

        $attendance->load(['user:id,name', 'breaks']);

        // 休憩行（DB値で初期化）
        $breakRows = $attendance->breaks->sortBy('break_start')->map(fn ($b) => [
            'start' => optional($b->break_start)->format('H:i'),
            'end'   => optional($b->break_end)->format('H:i'),
        ])->values()->all();

        $wd = $attendance->work_date ? \Carbon\Carbon::parse($attendance->work_date) : null;
        $dateYear     = $wd ? ($wd->year.'年') : '';
        $dateMonthDay = $wd ? $wd->isoFormat('M月D日') : '';
        $isPending    = ($attendance->status ?? null) === 'pending';

        // ★ 承認待ちなら最新の本人申請を取得
        $pendingRequest = null;
        if ($isPending) {
            $pendingRequest = ACR::where('attendance_id', $attendance->id)
                ->where('user_id', $user->id)
                ->where('status', ACR::STATUS_PENDING)
                ->latest('created_at')
                ->first();
        }

        // ★ 表示値を決定（承認待ち＋申請ありなら申請値を優先）
        $displayStart = optional($attendance->start_time)->format('H:i');
        $displayEnd   = optional($attendance->end_time)->format('H:i');
        if ($pendingRequest) {
            $displayStart = optional($pendingRequest->new_start_time)->format('H:i');
            $displayEnd   = optional($pendingRequest->new_end_time)->format('H:i');
            $breakRows = collect($pendingRequest->new_breaks ?? [])
                ->map(fn($b) => ['start' => $b['start'] ?? '', 'end' => $b['end'] ?? ''])
                ->values()->all();
        }

        // 空1行を最後に
        $breakRows[] = ['start' => '', 'end' => ''];

        return view('attendance.show', [
            'attendance'     => $attendance,
            'breakRows'      => $breakRows,
            'isPending'      => $isPending,
            'dateYear'       => $dateYear,
            'dateMonthDay'   => $dateMonthDay,
            'displayStart'   => $displayStart,
            'displayEnd'     => $displayEnd,
        ]);
    }

    // 打刻画面
    public function create(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();

        $attendance = Attendance::with('breaks')
        ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $hasOngoingBreak = false;

        if ($attendance) {
            $hasOpenInMem = $attendance->breaks
                ->contains(fn($b) => $b->break_start !== null && $b->break_end === null);

            $hasOpenInDb = \App\Models\BreakTime::query()
                ->where('attendance_id', $attendance->id)
                ->whereNotNull('break_start')
                ->whereNull('break_end')
                ->exists();

            $hasOngoingBreak = $hasOpenInMem || $hasOpenInDb;
        }

        $now = now();

        // ステータス判定
        $status = '勤務外';
        if ($attendance) {
            if ($attendance->end_time) {
                $status = '退勤済';
            } elseif ($hasOngoingBreak) {
                $status = '休憩中';
            } elseif ($attendance->start_time) {
                $status = '出勤中';
            }
        }

        return view('attendance.create', [
            'attendance' => $attendance,
            'status'     => $status,
            'date'       => $now->format('Y年n月j日'),
            'time'       => $now->format('H:i'),
        ]);
    }

    // 出勤
    public function startWork(StartWorkRequest $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['start_time' => now(), 'end_time' => null]
        );

        return redirect()->route('attendance.create')->with('success', '出勤時刻を記録しました。');
    }

    // 退勤
    public function endWork(EndWorkRequest $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->firstOrFail();

        // 未終了の休憩があればクローズ
        $attendance->breaks()
            ->whereNull('break_end')
            ->update(['break_end' => now()]);

        $attendance->end_time = now();
        $attendance->save();

        return redirect()->route('attendance.create')->with('success', '退勤時刻を記録しました。');
    }

    // 休憩開始（複数対応）
    public function startBreak(StartBreakRequest $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->firstOrFail();

        // 二重開始防止
        $hasOpen = $attendance->breaks()->whereNull('break_end')->exists();
        if ($hasOpen) {
            return back()->with('error', '既に休憩中です。');
        }

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => now(),
        ]);

        return redirect()->route('attendance.create')->with('success', '休憩開始を記録しました。');
    }

    // 休憩終了（複数対応）
    public function endBreak(EndBreakRequest $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->firstOrFail();

        $ongoingBreak = $attendance->breaks()
            ->whereNull('break_end')
            ->latest('break_start')
            ->first();

        if (!$ongoingBreak) {
            return back()->with('error', '休憩中ではありません。');
        }

        $ongoingBreak->update(['break_end' => now()]);

        return redirect()->route('attendance.create')->with('success', '休憩終了を記録しました。');
    }
}
