<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面の表示
     */
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

    /**
     * 出勤処理
     */
    public function startWork(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            if ($attendance->start_time) {
                return redirect()->route('attendance.create')->with('success', 'すでに出勤済みです。');
            }

            $attendance->start_time = Carbon::now();
            $attendance->save();
        } else {
            Attendance::create([
                'user_id' => $user->id,
                'work_date' => $today,
                'start_time' => Carbon::now(),
            ]);
        }

        return redirect()->route('attendance.create')->with('success', '出勤時刻を記録しました。');
    }

    /**
     * 退勤処理
     */
    public function endWork(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$attendance || !$attendance->start_time) {
            return redirect()->route('attendance.create')->with('error', '出勤記録が存在しません。');
        }

        if ($attendance->end_time) {
            return redirect()->route('attendance.create')->with('error', 'すでに退勤済みです。');
        }

        $attendance->end_time = Carbon::now();
        $attendance->save();

        return redirect()->route('attendance.create')->with('success', '退勤時刻を記録しました。');
    }

    /**
     * 休憩開始処理
     */
    public function startBreak(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.create')->with('error', '出勤情報が存在しません。');
        }

        if ($attendance->break_start_time) {
            return redirect()->route('attendance.create')->with('error', 'すでに休憩を開始しています。');
        }

        $attendance->break_start_time = Carbon::now();
        $attendance->save();

        return redirect()->route('attendance.create')->with('success', '休憩開始を記録しました。');
    }

    /**
     * 休憩終了処理
     */
    public function endBreak(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$attendance || !$attendance->break_start_time) {
            return redirect()->route('attendance.create')->with('error', '休憩が開始されていません。');
        }

        if ($attendance->break_end_time) {
            return redirect()->route('attendance.create')->with('error', 'すでに休憩終了を記録しています。');
        }

        $attendance->break_end_time = Carbon::now();
        $attendance->save();

        return redirect()->route('attendance.create')->with('success', '休憩終了を記録しました。');
    }

    public function show($id)
    {
        $user = Auth::user();

        // 勤怠をユーザーに紐づけて取得（他人の勤怠は見られない）
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance.list')
                ->with('error', '該当の勤怠データが見つかりませんでした。');
        }

        return view('attendance.show', compact('attendance'));
    }
}
