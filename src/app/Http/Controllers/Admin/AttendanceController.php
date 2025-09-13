<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Requests\Admin\UpdateAttendanceRequest;

class AttendanceController extends Controller
{
    // 日別一覧（?date=YYYY-MM-DD、無指定は今日）
    public function index(Request $request)
    {
        $dateStr = $request->query('date', now()->toDateString());

        try {
            $target = \Carbon\Carbon::createFromFormat('Y-m-d', $dateStr)->startOfDay();
        } catch (\Throwable $e) {
            $target = now()->startOfDay();
        }

        $attendances = \App\Models\Attendance::with(['user:id,name'])
            ->whereDate('work_date', $target->toDateString())
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

    // 詳細表示
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        // 一時デバッグ
        \Log::debug('REQ_CLASS: '.get_class($request));

        $v = $request->validated();

        $attendance->start_time       = $v['start_time']       ?? null;
        $attendance->end_time         = $v['end_time']         ?? null;
        $attendance->break_start_time = $v['break_start_time'] ?? null;
        $attendance->break_end_time   = $v['break_end_time']   ?? null;
        $attendance->note             = $v['note'];
        $attendance->save();

        $month = $attendance->work_date
            ? Carbon::parse($attendance->work_date)->format('Y-m')
            : Carbon::now()->format('Y-m');

        return redirect()->route('admin.attendances.staff.index', [
            'id'    => $attendance->user_id,
            'month' => $month,
        ])->with('success', '勤怠を修正しました。');
    }
}
