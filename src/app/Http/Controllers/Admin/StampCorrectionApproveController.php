<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StampCorrectionApproveController extends Controller
{
    // 詳細表示（管理者）
    public function show(AttendanceCorrectRequest $correction): View
    {
        $correction->load(['attendance.user']);

        return view('admin.stamp_requests.show', [
            'req'        => $correction,
            'attendance' => $correction->attendance,
            'user'       => $correction->attendance?->user,
            'isApproved' => $correction->status === AttendanceCorrectRequest::STATUS_APPROVED,
        ]);
    }

    // 承認（管理者）
    public function approve(AttendanceCorrectRequest $correction): RedirectResponse
    {
        if ($correction->status === AttendanceCorrectRequest::STATUS_APPROVED) {
            abort(422, 'Already approved');
        }

        DB::transaction(function () use ($correction) {
            $attendance = $correction->attendance()->lockForUpdate()->first();

            if ($attendance) {
                if (!is_null($correction->new_start_time)) {
                    $attendance->start_time = $correction->new_start_time;
                }
                if (!is_null($correction->new_end_time)) {
                    $attendance->end_time = $correction->new_end_time;
                }
                $attendance->status = 'ended'; // 設計に合わせて
                $attendance->save();
            }

            $correction->status      = AttendanceCorrectRequest::STATUS_APPROVED;
            $correction->approved_at = now();
            $correction->rejected_at = null;
            $correction->save();
        });

        return back()->with('success', '承認しました。勤怠に反映しました。');
    }

    // 却下（管理者）
    public function reject(AttendanceCorrectRequest $correction): RedirectResponse
    {
        if ($correction->status === AttendanceCorrectRequest::STATUS_APPROVED) {
            abort(422, 'Already approved');
        }

        DB::transaction(function () use ($correction) {
            $correction->status      = AttendanceCorrectRequest::STATUS_REJECTED;
            $correction->rejected_at = now();
            $correction->save();
        });

        return back()->with('success', '却下しました。');
    }
}
