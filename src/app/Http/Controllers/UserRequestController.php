<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest as ACR;

class UserRequestController extends Controller
{
    /**
     * 現在の利用者と管理者判定をガード横断で取得
     */
    private function resolveActor(): array
    {
        $webUser   = auth('web')->user();    // 一般ログイン
        $adminUser = auth('admin')->user();  // 管理ログイン

        $isAdmin = auth('admin')->check() || ($webUser?->is_admin ?? false);

        // 呼び出し側で必要なら web/admin どちらのユーザーか使い分けできるよう返す
        return [
            'user'    => $webUser ?? $adminUser,
            'isAdmin' => $isAdmin,
        ];
    }

    // 申請一覧（一般／管理者 共通）
    public function index(Request $request)
    {
        ['user' => $user, 'isAdmin' => $isAdmin] = $this->resolveActor();

        // タブの正規化
        $tab = $request->query('tab', 'pending');
        $tab = \in_array($tab, ['pending', 'approved'], true) ? $tab : 'pending';

        $attendanceId = (int) $request->query('attendance_id');

        // ============== 管理者：全ユーザー分 ==============
        if ($isAdmin) {
            $base = ACR::with(['attendance:id,work_date,user_id', 'attendance.user:id,name'])
                ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId));

            $pending  = (clone $base)->pending()->latest()->paginate(10, ['*'], 'pending_page');
            $approved = (clone $base)->approved()->latestApproved()->paginate(10, ['*'], 'approved_page');

            return view('requests.index', compact('tab', 'pending', 'approved', 'attendanceId', 'isAdmin'));
        }

        // ============== 一般ユーザー：本人のみ ==============
        $base = ACR::with(['attendance:id,work_date,user_id'])
            ->whereHas('attendance', fn($q) => $q->where('user_id', $user->id))
            ->when($attendanceId, fn ($q) => $q->where('attendance_id', $attendanceId));

        $pending  = (clone $base)->pending()->latest()->paginate(10, ['*'], 'pending_page');
        $approved = (clone $base)->approved()->latestApproved()->paginate(10, ['*'], 'approved_page');

        return view('requests.index', compact('tab', 'pending', 'approved', 'attendanceId', 'isAdmin'));
    }

    // 申請詳細（共通入口）：権限で適切な詳細へリダイレクト
    public function show(Request $request, ACR $stamp_request)
    {
        ['user' => $user, 'isAdmin' => $isAdmin] = $this->resolveActor();

        // 管理者は必ず「承認詳細」へ
        if ($isAdmin) {
            return redirect()->route('admin.stamp_requests.show', ['stamp_request' => $stamp_request->id]);
        }

        // 一般は本人の申請だけ許可（attendance 経由で所有確認）
        $stamp_request->loadMissing('attendance:id,user_id');

        if (!$stamp_request->attendance || $stamp_request->attendance->user_id !== $user->id) {
            abort(403);
        }

        if (!$stamp_request->attendance_id) {
            return redirect()->route('stamp_requests.index')
                ->with('error', 'この申請には勤怠情報が紐づいていません。');
        }

        return redirect()->route('attendance.show', ['attendance' => $stamp_request->attendance_id]);
    }
}
