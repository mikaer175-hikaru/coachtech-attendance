<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest as ACR;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class StampCorrectionApproveController extends Controller
{
    // 詳細表示（管理者）
    public function show(ACR $stamp_request): View
    {
        $stamp_request->load(['attendance.user']);

        return view('admin.stamp_requests.show', [
            'req'        => $stamp_request,
            'attendance' => $stamp_request->attendance,
            'user'       => $stamp_request->attendance?->user,
            'isApproved' => $stamp_request->status === ACR::STATUS_APPROVED,
        ]);
    }

    // 承認（管理者）
    public function approve(ACR $stamp_request): RedirectResponse
    {
        if ($stamp_request->status === ACR::STATUS_APPROVED) {
            abort(422, 'Already approved');
        }

        DB::transaction(function () use ($stamp_request) {
            $attendance = $stamp_request->attendance()->lockForUpdate()->first();

            if ($attendance) {
                if (!is_null($stamp_request->new_start_time)) {
                    $attendance->start_time = $stamp_request->new_start_time;
                }
                if (!is_null($stamp_request->new_end_time)) {
                    $attendance->end_time = $stamp_request->new_end_time;
                }

                // 休憩の同期
                $items = $this->extractBreakItems($stamp_request);
                if (!empty($items)) {
                    $attendance->breaks()->delete();

                    $workDate = $attendance->work_date instanceof Carbon
                        ? $attendance->work_date->toDateString()
                        : (string) $attendance->work_date;

                    foreach ($items as $b) {
                        $start = $b['start'] instanceof Carbon ? $b['start'] : Carbon::parse("$workDate {$b['start']}");
                        $end   = $b['end']   instanceof Carbon ? $b['end']   : Carbon::parse("$workDate {$b['end']}");

                        if ($end->lte($start)) {
                            continue;
                        }

                        $attendance->breaks()->create([
                            'break_start' => $start,
                            'break_end'   => $end,
                        ]);
                    }
                }

                $attendance->status = \App\Models\Attendance::STATUS_ENDED;
                $attendance->save();
            }

            $stamp_request->status      = ACR::STATUS_APPROVED;
            $stamp_request->approved_at = now();
            $stamp_request->rejected_at = null;
            $stamp_request->save();
        });

        return back()->with('success', '承認しました。勤怠に反映しました。');
    }

    /**
     * 申請に含まれる「休憩」情報を配列化して返す
     * 返り値の形：[['start' => '12:00', 'end' => '13:00'], ...]
     */
    private function extractBreakItems(ACR $r): array
    {
        // パターン1：配列/JSONで複数休憩（new_breaks）を持つケース
        if (is_array($r->new_breaks ?? null)) {
            return collect($r->new_breaks)
                ->map(function ($b) {
                    return [
                        'start' => $b['start'] ?? null,
                        'end'   => $b['end']   ?? null,
                    ];
                })
                ->filter(fn ($b) => !empty($b['start']) && !empty($b['end']))
                ->values()
                ->all();
        }

        // パターン2：単一のレガシー項目（new_break_start_time/new_break_end_time）
        if (!empty($r->new_break_start_time) && !empty($r->new_break_end_time)) {
            return [[
                'start' => $r->new_break_start_time,
                'end'   => $r->new_break_end_time,
            ]];
        }

        // 休憩申請なし
        return [];
    }

    // 却下（管理者）
    public function reject(ACR $stamp_request): RedirectResponse
    {
        if ($stamp_request->status === ACR::STATUS_APPROVED) {
            abort(422, 'Already approved');
        }

        DB::transaction(function () use ($stamp_request) {
            $stamp_request->status      = ACR::STATUS_REJECTED;
            $stamp_request->rejected_at = now();
            $stamp_request->save();
        });

        return back()->with('success', '却下しました。');
    }
}
