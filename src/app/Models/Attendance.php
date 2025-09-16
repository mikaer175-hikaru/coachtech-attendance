<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'work_date'      => 'date',
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
    ];

    /**
     * ユーザーとのリレーション（必要に応じて）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩履歴（1対多）
     */
    public function breakTimes()
    {
        if ($this->break_start_time && $this->break_end_time) {
            return $this->break_end_time->diffInMinutes($this->break_start_time);
        }
        return $this->hasMany(BreakTime::class);
    }

    /**
     * 勤怠情報取得
     */
    public function getTotalDurationAttribute()
    {
        if ($this->start_time && $this->end_time) {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
            $break = $this->break_duration ?? 0;

            return $end->diffInMinutes($start) - $break;
        }

        return null;
    }

    public function getFormattedTotalDurationAttribute()
    {
        if ($this->total_duration !== null) {
            $hours = floor($this->total_duration / 60);
            $minutes = $this->total_duration % 60;
            return sprintf('%d:%02d', $hours, $minutes);
        }
        return '-';
    }
}
