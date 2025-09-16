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
        return $this->hasMany(BreakTime::class);
    }
}
