<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectRequest extends Model
{
    use HasFactory;

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
        'new_breaks'     => 'array',     // JSON⇄配列
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    // ステータス用スコープ
    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
    public function scopeRejected($q) { return $q->where('status', 'rejected'); }
}
