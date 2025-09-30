<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'attendance_id',
        'user_id',
        'new_start_time',
        'new_end_time',
        'new_breaks',
        'note',
        'status',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'new_start_time' => 'datetime',
        'new_end_time'   => 'datetime',
        'new_breaks'     => 'array',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attendance(): BelongsTo { return $this->belongsTo(Attendance::class); }
    public function scopePending(\Illuminate\Database\Eloquent\Builder $q)  { return $q->where('status', self::STATUS_PENDING); }
    public function scopeApproved(\Illuminate\Database\Eloquent\Builder $q) { return $q->where('status', self::STATUS_APPROVED); }
    public function scopeRejected(\Illuminate\Database\Eloquent\Builder $q) { return $q->where('status', self::STATUS_REJECTED); }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => '申請中',
            self::STATUS_APPROVED => '承認済',
            self::STATUS_REJECTED => '却下',
            default               => '不明',
        };
    }

    public function scopeOwnedBy(\Illuminate\Database\Eloquent\Builder $q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeLatestApproved(\Illuminate\Database\Eloquent\Builder $q)
    {
        return $q->orderByDesc('approved_at');
    }
}
