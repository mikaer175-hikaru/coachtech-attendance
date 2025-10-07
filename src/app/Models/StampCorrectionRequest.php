<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attendance(): BelongsTo { return $this->belongsTo(Attendance::class); }

    public function scopePending($q)  { return $q->where('status', self::STATUS_PENDING); }
    public function scopeApproved($q) { return $q->where('status', self::STATUS_APPROVED); }
    public function scopeRejected($q) { return $q->where('status', self::STATUS_REJECTED); }

    public function scopeLatestApproved($q)
    {
        return $q->orderByDesc('approved_at')->orderByDesc('id');
    }

    public function scopeLatest($q)
    {
        return $q->orderByDesc('created_at')->orderByDesc('id');
    }

    protected $appends = ['start_time', 'end_time'];
    public function getStartTimeAttribute() { return $this->new_start_time; }
    public function getEndTimeAttribute()   { return $this->new_end_time; }
}
