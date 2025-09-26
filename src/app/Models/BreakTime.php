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
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end'   => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotNull('break_start')->whereNull('break_end');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereNotNull('break_start')->whereNotNull('break_end');
    }

    public function scopeOldestFirst(Builder $query): Builder
    {
        return $query->orderBy('break_start');
    }

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

    public function getDurationHmAttribute(): string
    {
        $m = $this->duration_minutes;
        return sprintf('%d:%02d', intdiv($m, 60), $m % 60);
    }

    public function closeAt(Carbon $end): self
    {
        if (!$this->break_start || $this->break_end) {
            return $this;
        }
        $this->break_end = $end;
        $this->save();

        return $this;
    }
}
