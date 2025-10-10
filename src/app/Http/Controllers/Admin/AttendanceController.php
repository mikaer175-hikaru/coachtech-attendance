<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
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

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class, 'attendance_id')->orderBy('break_start');
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

        $workDate = Carbon::parse($attendance->work_date)->toDateString();

        $start = isset($v['start_time']) && $v['start_time'] !== null
            ? Carbon::parse("$workDate {$v['start_time']}")
            : null;

        $end = isset($v['end_time']) && $v['end_time'] !== null
            ? Carbon::parse("$workDate {$v['end_time']}")
            : null;

        $breakInputs = collect($v['breaks'] ?? [])
            ->filter(fn ($b) => ($b['start'] ?? null) || ($b['end'] ?? null)) // どちらか入っていれば拾う
            ->map(function ($b) use ($workDate) {
                $bs = $b['start'] ?? null;
                $be = $b['end']   ?? null;
                return [
                    'break_start' => $bs ? Carbon::parse("$workDate $bs") : null,
                    'break_end'   => $be ? Carbon::parse("$workDate $be") : null,
                ];
            })
            ->values();

        // 休憩の整合性チェック
        foreach ($breakInputs as $i => $b) {
            if (is_null($b['break_start']) xor is_null($b['break_end'])) {
                return back()
                    ->withErrors(["breaks.$i.start" => '休憩は開始と終了の両方を入力してください。'])
                    ->withInput();
            }
            if ($b['break_start'] && $b['break_end'] && $b['break_start']->gte($b['break_end'])) {
                return back()
                    ->withErrors(["breaks.$i.start" => '休憩の開始は終了より前である必要があります。'])
                    ->withInput();
            }
        }

        // 開始時刻でソートして連続比較
        $sorted = $breakInputs->filter(fn($b) => $b['break_start'] && $b['break_end'])
            ->sortBy('break_start')
            ->values();
        for ($i = 1; $i < $sorted->count(); $i++) {
            if ($sorted[$i - 1]['break_end']->gt($sorted[$i]['break_start'])) {
                return back()
                    ->withErrors(["breaks.$i.start" => '休憩が重複しています。時間帯を見直してください。'])
                    ->withInput();
            }
        }

        // 保存
        DB::transaction(function () use ($attendance, $start, $end, $breakInputs, $v) {
            $attendance->start_time = $start;
            $attendance->end_time   = $end;
            $attendance->note       = $v['note'] ?? null;
            $attendance->save();

            // 既存の休憩を削除してから作り直す
            $attendance->breaks()->delete();

            foreach ($breakInputs as $b) {
                if ($b['break_start'] && $b['break_end']) {
                    $attendance->breaks()->create([
                        'break_start' => $b['break_start'],
                        'break_end'   => $b['break_end'],
                    ]);
                }
            }
        });

        $month = Carbon::parse($attendance->work_date)->format('Y-m');

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
