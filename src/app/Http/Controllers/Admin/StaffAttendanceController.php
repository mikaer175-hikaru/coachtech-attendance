<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAttendanceController extends Controller
{
    // 一覧表示
    public function index(Request $request, int $id)
    {
        $user = User::find($id);
        if (!$user) abort(404);

        [$month, $start, $end] = $this->resolveMonth($request->query('month'));

        // 月全日リスト
        $days = $this->makeDays($start, $end);

        // 当月勤怠（休憩も一括）
        $attendances = Attendance::query()
            ->with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($a) {
                return $a->work_date instanceof \Carbon\CarbonInterface
                    ? $a->work_date->toDateString()
                    : (string) $a->work_date;
            });

        // 表示行（欠損日は空欄）
        $rows = [];
        foreach ($days as $d) {
            $a = $attendances[$d->toDateString()] ?? null;

            $rows[] = [
                'date'           => $d->toDateString(),
                'date_label'     => $d->locale('ja')->isoFormat('MM/DD(ddd)'),// 例: 10/13(月)
                'weekday'        => $d->locale('ja')->isoFormat('ddd'),
                'start_hm'       => $a?->start_hm ?? '',
                'end_hm'         => $a?->end_hm ?? '',
                'break_hm'       => $a?->break_hm ?? '',
                'worked_hm'      => $a?->worked_hm ?? '',
                'attendance_id'  => $a?->id,
            ];
        }

        return view('admin.attendance.staff-index', [
            'user'  => $user,
            'rows'  => $rows,
            'month' => $month->format('Y-m'),
            'prev'  => $month->subMonth()->format('Y-m'),
            'next'  => $month->addMonth()->format('Y-m'),
        ]);
    }

    // CSV出力
    public function downloadCsv(Request $request, int $id): StreamedResponse
    {
        $user = User::find($id);
        if (!$user) abort(404);

        [$month, $start, $end] = $this->resolveMonth($request->query('month'));

        $attendances = Attendance::query()
            ->with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->with(['breaks' => fn($q) => $q->orderBy('break_start')])
            ->orderBy('work_date')
            ->get()
            ->keyBy(function ($a) {
                return $a->work_date instanceof \Carbon\CarbonInterface
                    ? $a->work_date->toDateString()
                    : (string) $a->work_date;
            });

        $days     = $this->makeDays($start, $end);
        $filename = sprintf('attendance_%d_%s.csv', $user->id, $month->format('Y-m'));

        return response()->streamDownload(function () use ($days, $attendances) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計', '詳細可否']);

            foreach ($days as $d) {
                $a = $attendances[$d->toDateString()] ?? null;

                fputcsv($out, [
                    $d->toDateString(),
                    $a?->start_hm ?? '',
                    $a?->end_hm ?? '',
                    $a?->break_hm ?? '',
                    $a?->worked_hm ?? '',
                    $a?->id ? '可' : '—',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ===== private =====
    private function resolveMonth(?string $month): array
    {
        $m = ($month && preg_match('/^\d{4}-\d{2}$/', $month))
            ? CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth()
            : CarbonImmutable::now()->startOfMonth();

        return [$m, $m->copy(), $m->endOfMonth()];
    }

    private function makeDays(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = [];
        for ($d = $start; $d <= $end; $d = $d->addDay()) $days[] = $d;
        return $days;
    }
}
