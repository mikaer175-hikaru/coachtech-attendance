<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
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

    // 勤怠詳細
    public function show(Attendance $attendance)
    {
        $user = auth()->user();
        $attendance->load(['user:id,name', 'breaks']);

        $breakRows = $attendance->breaks->map(fn ($b) => [
            'start' => $b->break_start?->format('H:i'),
            'end'   => $b->break_end?->format('H:i'),
        ])->toArray();
        // 入力用の空1行
        $breakRows[] = ['start' => '', 'end' => ''];

        if ($user && $user->can('admin')) {
            return view('admin.attendance.show', compact('attendance'));
        }

        if (!$user || $attendance->user_id !== $user->id) {
            abort(403);
        }

        return view('attendance.show', compact('attendance', 'breakRows'));
    }

    // 出勤
    public function startWork(StartWorkRequest $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['start_time' => now(), 'end_time' => null] // 念のため end をクリア
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

        // 未終了の休憩があればクローズ（運用保護）
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

    // 管理者：勤怠更新（※break_times 一本化のため単一休憩カラムは扱わない）
    public function update(UserUpdateAttendanceRequest $request, Attendance $attendance)
    {
        // 承認待ち保護（念のため二重チェック）
        if (($attendance->status ?? null) === 'pending') {
            return back()->with('error', '承認待ちの勤怠は編集できません');
        }

        DB::transaction(function () use ($request, $attendance) {
            // 勤怠本体
            $attendance->start_time = $request->input('start_time') ? now()->parse($request->input('start_time')) : null;
            $attendance->end_time   = $request->input('end_time')   ? now()->parse($request->input('end_time'))   : null;
            $attendance->note       = $request->input('note');
            $attendance->save();

            // 休憩は一旦全削除→再作成（YAGNI：高度な差分更新は不要ならやらない）
            $attendance->breakTimes()->delete();

            $breaks = collect($request->input('breaks', []))
                ->filter(fn($b) => !empty($b['start']) || !empty($b['end']));

            foreach ($breaks as $b) {
                $attendance->breakTimes()->create([
                    'start_time' => !empty($b['start']) ? now()->parse($b['start']) : null,
                    'end_time'   => !empty($b['end'])   ? now()->parse($b['end'])   : null,
                ]);
            }
        });

        return redirect()
            ->route('attendance.show', $attendance->id)
            ->with('success', '勤怠を更新しました。');
    }

    // 管理者：日別一覧
    public function indexDaily(Request $request)
    {
        $request->validate(['date' => ['nullable', 'date']]);

        $target = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : now()->startOfDay();

        $attendances = Attendance::with(['user:id,name'])
            ->whereDate('work_date', $target->toDateString())
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attendance.index', [
            'attendances' => $attendances,
            'targetDate'  => $target->toDateString(),
            'titleDate'   => $target->format('Y年n月j日'),
            'prevDate'    => $target->copy()->subDay()->toDateString(),
            'nextDate'    => $target->copy()->addDay()->toDateString(),
        ]);
    }

    // 管理者：月次一覧
    public function indexMonthly(Request $request, User $user)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse("{$month}-01")->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        return view('admin.attendance.index-monthly', compact('user', 'month', 'attendances'));
    }

    // 管理者：CSV出力（休憩は合計分を出力）
    public function exportMonthlyCsv(Request $request, User $user): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse("{$month}-01")->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $rows = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $filename = "attendance_{$user->id}_{$month}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['日付', '出勤', '退勤', '休憩合計(分)', '備考']);
            foreach ($rows as $r) {
                $totalBreak = $r->breaks->sum(function ($b) {
                    if (!$b->break_start || !$b->break_end) return 0;
                    return $b->break_start->diffInMinutes($b->break_end);
                });

                fputcsv($out, [
                    optional($r->work_date)->toDateString(),
                    optional($r->start_time)->format('Y-m-d H:i:s'),
                    optional($r->end_time)->format('Y-m-d H:i:s'),
                    $totalBreak,
                    $r->note,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
