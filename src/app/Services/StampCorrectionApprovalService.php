<?php

namespace App\Services;

use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\DB;

class StampCorrectionApprovalService
{
    public function approve(StampCorrectionRequest $req, int $adminUserId): void
    {
        if ($req->status === 'approved') {
            return;
        }

        DB::transaction(function () use ($req) {
            $req->status      = 'approved';
            $req->approved_at = now();
            $req->rejected_at = null;
            $req->save();

            $attendance = $req->attendance()->lockForUpdate()->first();
            if ($attendance && ($attendance->status ?? null) === 'pending') {
                $attendance->status = 'ended'; // 実装に合わせて変更可
                $attendance->save();
            }
        });
    }
}
