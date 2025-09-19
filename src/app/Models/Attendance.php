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
    public function scopeOnDate(Builder $q, Carbon|string $date): Builder
    {
        $d = $date instanceof Carbon ? $date->toDateString() : (string) $date;
        return $q->whereDate('work_date', $d);
    }

    public function scopeBetweenDates(Builder $q, Carbon|string $from, Carbon|string $to): Builder
    {
        $f = $from instanceof Carbon ? $from->toDateString() : (string) $from;
        $t = $to   instanceof Carbon ? $to->toDateString()   : (string) $to;
        return $q->whereDate('work_date', '>=', $f)->whereDate('work_date', '<=', $t);
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
        // hasMany の集計
        $breaks = $this->relationLoaded('breaks') ? $this->breaks : $this->getRelationValue('breaks');
        if ($breaks && $breaks->count() > 0) {
            return (int) $breaks->sum(function ($b) {
                if (!$b->break_start || !$b->break_end) return 0;
                $start = $b->break_start instanceof Carbon ? $b->break_start : Carbon::parse($b->break_start);
                $end   = $b->break_end   instanceof Carbon ? $b->break_end   : Carbon::parse($b->break_end);
                return max(0, $start->diffInMinutes($end));
            });
        }

        // 単一休憩カラム（移行中サポート）
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

    public function getStartHmAttribute(): string {
        return $this->start_time instanceof Carbon ? $this->start_time->format('H:i') : '';
    }
    public function getEndHmAttribute(): string {
        return $this->end_time instanceof Carbon ? $this->end_time->format('H:i') : '';
    }
    public function getBreakHmAttribute(): string {
        $m = $this->total_break_minutes;
        return $m > 0 ? sprintf('%d:%02d', intdiv($m, 60), $m % 60) : '';
    }
    public function getBreakMinutesAttribute(): int {
        return $this->total_break_minutes; // 後方互換
    }
}