<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\Attendance\StartWorkRequest;
use App\Http\Requests\Attendance\EndWorkRequest;
use App\Http\Requests\Attendance\StartBreakRequest;
use App\Http\Requests\Attendance\EndBreakRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function show(Attendance $attendance)
    {
        $user = auth()->user();

        if ($user && $user->can('admin')) {
            $attendance->load(['user:id,name', 'breaks']);
            return view('admin.attendances.show', compact('attendance'));
        }

        if (!$user || $attendance->user_id !== $user->id) {
            abort(403);
        }

        $attendance->load(['breaks']);
        return view('attendances.show', compact('attendance'));
    }

    // 出勤処理
    public function startWork(StartWorkRequest $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['start_time' => now()]
        );

        return redirect()->route('attendance.create')->with('success', '出勤時刻を記録しました。');
    }

    // 退勤処理
    public function endWork(EndWorkRequest $request)
    {
        $user = Auth::user();
        $today = \Carbon\Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $attendance->end_time = now();
        $attendance->save();

        return redirect()->route('attendance.create')->with('success', '退勤時刻を記録しました。');
    }

    // 休憩開始処理（複数対応）
    public function startBreak(StartBreakRequest $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => now(),
        ]);

        return redirect()->route('attendance.create')->with('success', '休憩開始を記録しました。');
    }

    // 休憩終了処理（複数対応）
    public function endBreak(EndBreakRequest $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $ongoingBreak = $attendance->breakTimes()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        $ongoingBreak->end_time = now();
        $ongoingBreak->save();

        return redirect()->route('attendance.create')->with('success', '休憩終了を記録しました。');
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $v = $request->validated();

        $attendance->start_time       = $v['start_time']       ?? null;
        $attendance->end_time         = $v['end_time']         ?? null;
        $attendance->break_start_time = $v['break_start_time'] ?? null;
        $attendance->break_end_time   = $v['break_end_time']   ?? null;
        $attendance->note             = $v['note'];
        $attendance->save();

        $month = $attendance->work_date
            ? Carbon::parse($attendance->work_date)->format('Y-m')
            : now()->format('Y-m');

        return redirect()->route('admin.attendance.staff.monthly', [
            'user'  => $attendance->user_id,
            'month' => $month,
        ])->with('success', '勤怠を修正しました。');
    }

    // 日別一覧（管理者）
    public function indexDaily(Request $request)
    {
        $request->validate(['date' => ['nullable', 'date']]);

        $target = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : now()->startOfDay();

        $attendances = Attendance::with(['user:id,name'])
            ->whereDate('work_date', $target->toDateString())   // ※カラム名を統一
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attendances.index', [
            'attendances' => $attendances,
            'targetDate'  => $target->toDateString(),
            'titleDate'   => $target->format('Y年n月j日'),
            'prevDate'    => $target->copy()->subDay()->toDateString(),
            'nextDate'    => $target->copy()->addDay()->toDateString(),
        ]);
    }

    // 月次勤怠一覧（管理者）
    public function indexMonthly(Request $request, User $user)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse("{$month}-01")->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        return view('admin.attendances.index-monthly', compact('user', 'month', 'attendances'));
    }

    // CSV
    public function exportMonthlyCsv(Request $request, User $user): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse("{$month}-01")->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $rows = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get(['work_date', 'start_time', 'end_time', 'break_start_time', 'break_end_time', 'note']);

        $filename = "attendance_{$user->id}_{$month}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['日付', '出勤', '退勤', '休憩開始', '休憩終了', '備考']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->work_date,
                    $r->start_time,
                    $r->end_time,
                    $r->break_start_time,
                    $r->break_end_time,
                    $r->note,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
