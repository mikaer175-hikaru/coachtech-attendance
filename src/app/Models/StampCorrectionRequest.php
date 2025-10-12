<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    protected $table = 'attendance_correct_requests';

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

    protected $appends = ['start_time', 'end_time'];
    public function getStartTimeAttribute() { return $this->new_start_time; }
    public function getEndTimeAttribute()   { return $this->new_end_time; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attendance(): BelongsTo { return $this->belongsTo(Attendance::class); }

    // ---- スコープ ----
    /** 申請者で絞る */
    public function scopeOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopePending($q)  { return $q->where('status', self::STATUS_PENDING); }
    public function scopeApproved($q) { return $q->where('status', self::STATUS_APPROVED); }
    public function scopeRejected($q) { return $q->where('status', self::STATUS_REJECTED); }

    /** 承認済みの新しい順（承認日時→ID） */
    public function scopeLatestApproved($q)
    {
        return $q->orderByDesc('approved_at')->orderByDesc('id');
    }

    /** 作成の新しい順（pending 用） */
    public function scopeLatest($q)
    {
        return $q->orderByDesc('created_at')->orderByDesc('id');
    }

}

