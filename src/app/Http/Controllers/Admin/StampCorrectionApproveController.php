<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StampCorrectionApproveController extends Controller
{
    /**
     * 修正申請の詳細表示（管理者）
     */
    public function show(AttendanceCorrectRequest $request): View
    {
        $request->load(['attendance.user']);

        return view('admin.stamp_requests.approve', [
            'req'        => $request,
            'attendance' => $request->attendance,
            'user'       => $request->attendance?->user,
            'isApproved' => $request->status === 'approved',
        ]);
    }

    /**
     * 修正申請の承認処理（管理者）
     */
    public function approve(AttendanceCorrectRequest $request, Request $http): RedirectResponse
    {
        if ($request->status === 'approved') {
            return back()->with('success', 'すでに承認済みです。');
        }

        DB::transaction(function () use ($request) {
            // 1) 対象勤怠を取得
            $attendance = $request->attendance()->lockForUpdate()->first();

            if ($attendance) {
                // 修正後の値を反映（nullは上書きしない）
                if (!is_null($request->new_start_time)) {
                    $attendance->start_time = $request->new_start_time;
                }
                if (!is_null($request->new_end_time)) {
                    $attendance->end_time = $request->new_end_time;
                }

                // 承認済みステータスへ
                $attendance->status = 'ended'; // or 'approved' あなたの設計に合わせる
                $attendance->save();
            }

            // 2) 申請自体を承認済みに更新
            $request->status      = 'approved';
            $request->approved_at = now();
            $request->rejected_at = null;
            $request->save();
        });

        return back()->with('success', '承認しました。勤怠に反映しました。');
    }
}
