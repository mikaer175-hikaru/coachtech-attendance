<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Http\Requests\Admin\UpdateAttendanceRequest;

class AdminAttendanceController extends Controller
{
    // 日別一覧（?date=YYYY-MM-DD、無指定は今日
    public function index(Request $request)
    {
        $v = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $target = isset($v['date'])
            ? Carbon::parse($v['date'])->startOfDay()
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

    //勤怠詳細（管理者用）
    public function show(Attendance $attendance)
    {
        $attendance->loadMissing('user:id,name');

        return view('admin.attendance.show', [
            'attendance' => $attendance,
        ]);
    }

    // 勤怠更新（PG09の保存先）
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $v = $request->validated();

        $attendance->start_time       = $v['start_time']       ?? null;
        $attendance->end_time         = $v['end_time']         ?? null;
        $attendance->break_start_time = $v['break_start_time'] ?? null;
        $attendance->break_end_time   = $v['break_end_time']   ?? null;
        $attendance->note             = $v['note'] ?? null;
        $attendance->save();

        $month = $attendance->work_date
            ? Carbon::parse($attendance->work_date)->format('Y-m')
            : now()->format('Y-m');

        return redirect()->route('admin.attendance.staff', [
            'id'    => $attendance->user_id,
            'month' => $month,
        ])->with('success', '勤怠を修正しました。');
    }

    // スタッフの月次勤怠（PG11）
    public function showByStaff(Request $request, int $id)
    {
        // $month = $request->query('month', now()->format('Y-m'));
        // 月次の勤怠一覧を取得してビューに渡す想定
        abort(501, 'showByStaff は後で実装'); // 仮：未実装明示
    }
}
