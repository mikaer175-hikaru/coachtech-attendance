<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Http\Requests\EndWorkRequest;
use App\Http\Requests\StartWorkRequest;
use App\Http\Requests\StartBreakRequest;
use App\Http\Requests\EndBreakRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // 勤怠登録画面の表示
    public function create()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $status = '勤務外';

        if ($attendance) {
            if ($attendance->end_time) {
                $status = '退勤済';
            } elseif ($attendance->break_start_time && !$attendance->break_end_time) {
                $status = '休憩中';
            } elseif ($attendance->start_time) {
                $status = '出勤中';
            }
        }

        $now = Carbon::now();
        $date = $now->format('Y年n月j日(D)');
        $time = $now->format('H:i');

        return view('attendance.create', compact('attendance', 'status', 'date', 'time'));
    }

    // 出勤処理
    public function startWork(StartWorkRequest $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['start_time' => now()]
        );

        return redirect()->route('attendance.create')->with('success', '出勤時刻を記録しました。');
    }

    // 退勤処理
    public function endWork(EndWorkRequest $request)
    {
        $user = Auth::user();
        $today = \Carbon\Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $attendance->end_time = now();
        $attendance->save();

        return redirect()->route('attendance.create')->with('success', '退勤時刻を記録しました。');
    }

    // 休憩開始処理（複数対応）
    public function startBreak(StartBreakRequest $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => now(),
        ]);

        return redirect()->route('attendance.create')->with('success', '休憩開始を記録しました。');
    }

    // 休憩終了処理（複数対応）
    public function endBreak(EndBreakRequest $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $ongoingBreak = $attendance->breakTimes()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        $ongoingBreak->end_time = now();
        $ongoingBreak->save();

        return redirect()->route('attendance.create')->with('success', '休憩終了を記録しました。');
    }

    // 勤怠詳細
    public function show($id)
    {
        $user = Auth::user();

        $attendance = Attendance::with('breakTimes')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // 休憩データを breakRows 配列に整形（HH:MM 形式）
        $breakRows = $attendance->breakTimes
            ->map(fn ($break) => [
                'start' => $break->start_time?->format('H:i'),
                'end'   => $break->end_time?->format('H:i'),
            ])
            ->toArray();

        // 空行を1行追加（新規入力用）
        $breakRows[] = ['start' => '', 'end' => ''];

        return view('attendance.show', compact('attendance', 'breakRows'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $targetMonth = $request->input('month', now()->format('Y-m'));
        $carbon = Carbon::createFromFormat('Y-m', $targetMonth);

        // 勤怠情報取得（指定月の全日分）
        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('work_date', $carbon->year)
            ->whereMonth('work_date', $carbon->month)
            ->orderBy('work_date', 'asc')
            ->get();

        return view('attendance.list', [
            'attendances' => $attendances,
            'currentMonth' => $carbon->format('Y年m月'),
            'prevMonth' => $carbon->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $carbon->copy()->addMonth()->format('Y-m'),
        ]);
    }

    // 修正申請の保存
    public function storeCorrection(CorrectionRequest $request, Attendance $attendance)
    {
        abort_if($attendance->user_id !== Auth::id(), 403);

        // "HH:mm" → 当日の DateTime に変換
        $toDateTime = function (?string $hm) use ($attendance) {
            if (!$hm) return null;
            [$h,$m] = explode(':', $hm);
            return $attendance->work_date->copy()->setTime((int)$h, (int)$m);
        };

        AttendanceCorrectRequest::create([
            'attendance_id'  => $attendance->id,
            'user_id'        => Auth::id(),
            'new_start_time' => $toDateTime($request->input('start_time')),
            'new_end_time'   => $toDateTime($request->input('end_time')),
            'new_breaks'     => $request->input('breaks', []), // casts=array
            'note'           => $request->string('note'),
            'status'         => 'pending',
        ]);

        // 対象勤怠を承認待ちへ
        $attendance->update(['status' => 'pending']);

        return back()->with('success', '修正申請を送信しました。承認待ちです。');
    }

    // （任意）申請一覧を同居させる場合
    public function requestIndex()
    {
        $requests = AttendanceCorrectRequest::with(['attendance','attendance.user'])
            ->where('user_id', Auth::id())
            ->orderByDesc('id')
            ->paginate(10);

        return view('stamp_requests.index', compact('requests'));
    }
}
