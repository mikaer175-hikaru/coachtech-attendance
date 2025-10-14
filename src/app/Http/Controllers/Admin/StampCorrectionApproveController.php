<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest as ACR;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
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
                $attendance->status = 'ended'; // 設計に合わせて
                $attendance->save();
            }

            $stamp_request->status      = ACR::STATUS_APPROVED;
            $stamp_request->approved_at = now();
            $stamp_request->rejected_at = null;
            $stamp_request->save();
        });

        return back()->with('success', '承認しました。勤怠に反映しました。');
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
