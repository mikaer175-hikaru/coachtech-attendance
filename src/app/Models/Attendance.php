<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    // ===== ステータス定数（ビューやコントローラでのマジック文字列回避） =====
    public const STATUS_OFF      = 'off';      // 勤務外
    public const STATUS_WORKING  = 'working';  // 出勤中
    public const STATUS_BREAKING = 'breaking'; // 休憩中
    public const STATUS_ENDED    = 'ended';    // 退勤済

    // ===== 一括代入許可カラム（設計に応じて note/status を追加・削除） =====
    protected $fillable = [
        'user_id',
        'work_date',
        'start_time',
        'end_time',
        'break_start_time',
        'break_end_time',
        'status', // 使わない場合は外してください
        'note',   // 使わない場合は外してください
    ];

    // ===== 型変換 =====
    protected $casts = [
        'work_date'        => 'date',
        'start_time'       => 'datetime',
        'end_time'         => 'datetime',
        'break_start_time' => 'datetime',
        'break_end_time'   => 'datetime',
    ];

    // ===== リレーション =====

    // ユーザー
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 休憩（将来の複数休憩テーブル用。作成前でも定義してOK）
    public function breaks(): HasMany
    {
        // break_times を作成後に有効化されます
        return $this->hasMany(BreakTime::class)->orderBy('break_start');
    }

    // ===== クエリスコープ =====

    // 自分の勤怠のみ
    public function scopeMine(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // 指定日
    public function scopeOfDate(Builder $query, Carbon|string $date): Builder
    {
        $d = $date instanceof Carbon ? $date->toDateString() : (string) $date;
        return $query->whereDate('work_date', $d);
    }

    // 指定年月
    public function scopeOfMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('work_date', $year)->whereMonth('work_date', $month);
    }

    // ===== 表示用アクセサ =====

    // ステータスの日本語ラベル
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OFF      => '勤務外',
            self::STATUS_WORKING  => '出勤中',
            self::STATUS_BREAKING => '休憩中',
            self::STATUS_ENDED    => '退勤済',
            default               => '不明',
        };
    }

    // 休憩合計（分）
    public function getTotalBreakMinutesAttribute(): int
    {
        // 将来の hasMany(breaks) が使える場合はそちらを優先
        if ($this->relationLoaded('breaks') || method_exists($this, 'breaks')) {
            $collection = $this->getRelationValue('breaks');
            if ($collection && $collection->count() > 0) {
                return (int) $collection->sum(function ($b) {
                    if (!$b->break_start || !$b->break_end) {
                        return 0;
                    }
                    /** @var Carbon $start */
                    $start = $b->break_start instanceof Carbon ? $b->break_start : Carbon::parse($b->break_start);
                    /** @var Carbon $end */
                    $end   = $b->break_end   instanceof Carbon ? $b->break_end   : Carbon::parse($b->break_end);
                    return max(0, $start->diffInMinutes($end));
                });
            }
        }

        // 単一休憩カラム（現行スキーマ）
        if ($this->break_start_time && $this->break_end_time) {
            return max(0, $this->break_start_time->diffInMinutes($this->break_end_time));
        }

        return 0;
    }

    // 実働（分）
    public function getWorkedMinutesAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        $baseMinutes = $this->start_time->diffInMinutes($this->end_time);
        return max(0, $baseMinutes - $this->total_break_minutes);
    }

    // 実働（HH:MM 文字列）
    public function getWorkedHmAttribute(): string
    {
        $m = $this->worked_minutes;
        return sprintf('%d:%02d', intdiv($m, 60), $m % 60);
    }
}

