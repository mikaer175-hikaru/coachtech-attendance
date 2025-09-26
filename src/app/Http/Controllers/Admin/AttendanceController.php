<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        return $this->indexDaily($request);
    }

    // 日別一覧（?date=YYYY-MM-DD、無指定は今日）
    public function indexDaily(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

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

    public function show(Attendance $attendance)
    {
        $attendance->load(['user:id,name', 'breaks']);
        return view('admin.attendance.show', compact('attendance'));
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $v = $request->validated();

        $attendance->start_time       = $v['start_time']       ?? null;
        $attendance->end_time         = $v['end_time']         ?? null;
        $attendance->break_start_time = $v['break_start_time'] ?? null;
        $attendance->break_end_time   = $v['break_end_time']   ?? null;
        $attendance->note             = $v['note']; // required

        $attendance->save();

        $month = $attendance->work_date
            ? Carbon::parse($attendance->work_date)->format('Y-m')
            : now()->format('Y-m');

        return redirect()->route('admin.attendance.staff.index', [
            'id'    => $attendance->user_id,
            'month' => $month,
        ])->with('success', '勤怠を修正しました。');
    }

    // 月次勤怠一覧
    public function indexMonthly(Request $request, int $id)
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $user = User::findOrFail($id);

        return view('admin.attendance.index-monthly', compact('user', 'month', 'attendances'));
    }

    public function exportMonthlyCsv(Request $request, int $id): StreamedResponse
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $rows = Attendance::where('user_id', $id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get(['work_date', 'start_time', 'end_time', 'break_start_time', 'break_end_time', 'note']);

        $filename = "attendance_{$id}_{$month}.csv";

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
