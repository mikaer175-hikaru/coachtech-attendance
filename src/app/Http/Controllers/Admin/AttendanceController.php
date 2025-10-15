<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Models\StampCorrectionRequest as ACR;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $request->validate(['date' => ['nullable', 'date']]);

        $target = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : now()->startOfDay();

        $attendances = Attendance::with(['user:id,name', 'breaks'])
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

    // 管理者：勤怠詳細
    public function show(Attendance $attendance)
    {
        // 必要な関連をロード
        $attendance->load(['user:id,name', 'breaks']);

        // 休憩行（H:i）＋空1行
        $breakRows = $attendance->breaks->sortBy('break_start')->map(fn ($b) => [
            'start' => optional($b->break_start)->format('H:i'),
            'end'   => optional($b->break_end)->format('H:i'),
        ])->values()->toArray();
        $breakRows[] = ['start' => '', 'end' => ''];

        // 日付表示
        $wd = $attendance->work_date instanceof Carbon
            ? $attendance->work_date
            : ($attendance->work_date ? Carbon::parse($attendance->work_date) : null);
        $dateYear     = $wd ? ($wd->year . '年') : '';
        $dateMonthDay = $wd ? $wd->isoFormat('M月D日') : '';

        // 承認待ち判定（DBのstatusをそのまま使用）
        $isPending = ($attendance->status ?? null) === 'pending';

        // 承認待ちなら最新の申請をプレビュー用に取得（任意）
        $pendingRequest = null;
        if ($isPending) {
            $pendingRequest = ACR::where('attendance_id', $attendance->id)
                ->latest('created_at')
                ->first();
        }

        return view('admin.attendance.show', [
            'attendance'     => $attendance,
            'breakRows'      => $breakRows,
            'isPending'      => $isPending,
            'dateYear'       => $dateYear,
            'dateMonthDay'   => $dateMonthDay,
            'pendingRequest' => $pendingRequest,
        ]);
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
            ->filter(fn ($b) => ($b['start'] ?? null) || ($b['end'] ?? null))
            ->map(function ($b) use ($workDate) {
                $bs = $b['start'] ?? null;
                $be = $b['end']   ?? null;
                return [
                    'break_start' => $bs ? Carbon::parse("$workDate $bs") : null,
                    'break_end'   => $be ? Carbon::parse("$workDate $be") : null,
                ];
            })
            ->values();

        // 整合性チェック
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

        // 重複チェック
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

            // 既存休憩を全削除→作り直し
            $attendance->breaks()->delete();

            foreach ($breakInputs as $b) {
                if ($b['break_start'] && $b['break_end']) {
                    $attendance->breaks()->create($b);
                }
            }
        });

        $month = Carbon::parse($attendance->work_date)->format('Y-m');

        return redirect()->route('admin.attendance.staff.index', [
            'id'    => $attendance->user_id,
            'month' => $month,
        ])->with('success', '勤怠を修正しました。');
    }

    // 月次勤怠一覧（休憩も読み込む）
    public function indexMonthly(Request $request, int $id)
    {
        $month = $request->query('month', \Carbon\Carbon::now()->format('Y-m'));

        $start = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        // ★ 追加：前月・翌月・表示用
        $prev  = $start->copy()->subMonth()->format('Y-m');
        $next  = $start->copy()->addMonth()->format('Y-m');
        $monthLabel = $start->format('Y-m'); // 画面中央に出す用（任意）

        $attendances = \App\Models\Attendance::with('breaks')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $user = \App\Models\User::findOrFail($id);

        return view('admin.attendance.staff-index', [
            'user'         => $user,
            'attendances'  => $attendances,
            'month'        => $monthLabel, // 既存の $month 参照に合わせるならこれでOK
            'prev'         => $prev,       // ★ 追加
            'next'         => $next,       // ★ 追加
        ]);
    }

    // CSV出力：休憩は合計分で出力（break_times を集計）
    public function exportMonthlyCsv(Request $request, int $id): StreamedResponse
    {
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $rows = Attendance::with('breaks')
            ->where('user_id', $id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $filename = "attendance_{$id}_{$month}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // Excel 対策
            fwrite($out, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($out, ['日付', '出勤', '退勤', '休憩合計(分)', '備考']);

            foreach ($rows as $r) {
                // 休憩合計(分)
                $totalBreak = $r->breaks->sum(function ($b) {
                    if (!$b->break_start || !$b->break_end) return 0;
                    return $b->break_start->diffInMinutes($b->break_end);
                });

                // 日付は1列目、出勤/退勤は時刻だけ（H:i）で出力
                $date  = optional($r->work_date)->toDateString();
                $start = $r->start_time ? $r->start_time->format('H:i') : '';
                $end   = $r->end_time   ? $r->end_time->format('H:i')   : '';

                fputcsv($out, [$date, $start, $end, $totalBreak, $r->note]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
