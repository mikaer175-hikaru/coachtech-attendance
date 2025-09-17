<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BreakTime extends Model
{
    protected $fillable = [
        'attendance_id',
        'break_start', // datetime
        'break_end',   // datetime
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end'   => 'datetime',
    ];

    /**
     * 親：勤怠
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * スコープ：未クローズ（休憩戻 まだ）
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotNull('break_start')->whereNull('break_end');
    }

    /**
     * スコープ：クローズ済（開始・終了 両方あり）
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereNotNull('break_start')->whereNotNull('break_end');
    }

    /**
     * スコープ：開始時刻の昇順
     */
    public function scopeOldestFirst(Builder $query): Builder
    {
        return $query->orderBy('break_start');
    }

    /**
     * アクセサ：休憩時間（分）
     */
    public function getDurationMinutesAttribute(): int
    {
        if (!$this->break_start || !$this->break_end) {
            return 0;
        }
        /** @var Carbon $start */
        $start = $this->break_start;
        /** @var Carbon $end */
        $end = $this->break_end;

        return max(0, $start->diffInMinutes($end));
    }

    /**
     * アクセサ：休憩時間（HH:MM）
     */
    public function getDurationHmAttribute(): string
    {
        $m = $this->duration_minutes;
        return sprintf('%d:%02d', intdiv($m, 60), $m % 60);
    }

    /**
     * ヘルパ：終了をセット（検証は呼び出し元のFormRequestで行う想定）
     */
    public function closeAt(Carbon $end): self
    {
        // 早期リターン：開始がない or 既に終了済みなら何もしない
        if (!$this->break_start || $this->break_end) {
            return $this;
        }
        $this->break_end = $end;
        $this->save();

        return $this;
    }
}
