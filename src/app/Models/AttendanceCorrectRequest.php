<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectRequest extends Model
{
    protected $fillable = [
        'attendance_id','user_id','new_start_time','new_end_time','new_breaks','note','status',
    ];
    protected $casts = [
        'new_start_time' => 'datetime',
        'new_end_time'   => 'datetime',
        'new_breaks'     => 'array', // ← JSONを配列で扱える
    ];

    public function attendance(): BelongsTo { return $this->belongsTo(Attendance::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
