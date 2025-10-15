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

    // ===== ステータス定数 =====
    public const STATUS_OFF      = 'off';      // 勤務外
    public const STATUS_WORKING  = 'working';  // 出勤中
    public const STATUS_BREAKING = 'breaking'; // 休憩中
    public const STATUS_ENDED    = 'ended';    // 退勤済

    // ===== 一括代入許可 =====
    protected $fillable = [
        'user_id',
        'work_date',
        'start_time',
        'end_time',
        'status',
        'note',
    ];

    // ===== 型変換 =====
    protected $casts = [
        'work_date'  => 'date',
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    // ===== リレーション =====

    /** ユーザー */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 複数休憩 */
    public function breaks(): HasMany
    {
        return $this->hasMany(BreakTime::class)->orderBy('break_start');
    }

    // ===== ロジック =====

    /** 休憩中かどうか（複数休憩 or レガシー単休憩） */
    public function isBreaking(): bool
    {
        $ongoingInBreaks = $this->breaks()->whereNull('break_end')->exists();
        $legacyOngoing   = !empty($this->break_start_time) && empty($this->break_end_time); // レガシー用
        return $ongoingInBreaks || $legacyOngoing;
    }

    /** 現在のステータス（出退勤/休憩から算出） */
    public function getComputedStatusAttribute(): string
    {
        if ($this->end_time) {
            return self::STATUS_ENDED;
        }
        if ($this->isBreaking()) {
            return self::STATUS_BREAKING;
        }
        if ($this->start_time) {
            return self::STATUS_WORKING;
        }
        return self::STATUS_OFF;
    }

    // ===== クエリスコープ =====

    /** 指定年月の勤怠 */
    public function scopeOfMonth(Builder $q, int $year, int $month): Builder
    {
        return $q->whereYear('work_date', $year)->whereMonth('work_date', $month);
    }

    /** 指定日の勤怠 */
    public function scopeOnDate(Builder $q, Carbon|string $date): Builder
    {
        $d = $date instanceof Carbon ? $date->toDateString() : (string) $date;
        return $q->whereDate('work_date', $d);
    }

    /** 期間（両端含む） */
    public function scopeBetweenDates(Builder $q, Carbon|string $from, Carbon|string $to): Builder
    {
        $f = $from instanceof Carbon ? $from->toDateString() : (string) $from;
        $t = $to   instanceof Carbon ? $to->toDateString()   : (string) $to;
        return $q->whereDate('work_date', '>=', $f)
                ->whereDate('work_date', '<=', $t);
    }

    // ===== 表示用アクセサ =====

    /** ステータス日本語ラベル */
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

    /** 休憩合計（分） */
    public function getTotalBreakMinutesAttribute(): int
    {
        $breaks = $this->breaks;

        if ($breaks->isEmpty()) {
            return 0;
        }

        return (int) $breaks->sum(function (BreakTime $b) {
            if (!$b->break_start || !$b->break_end) {
                return 0;
            }
            // BreakTime 側は casts で datetime を想定
            return $b->break_start->diffInMinutes($b->break_end);
        });
    }

    /** 休憩（H:MM） */
    public function getBreakHmAttribute(): string
    {
        $m = $this->total_break_minutes;

        if ($m === 0) {
            // レコードが存在するがゼロ分であれば 0:00、レコード自体なければ空
            $hasAny = $this->relationLoaded('breaks')
                ? $this->breaks->isNotEmpty()
                : $this->breaks()->exists();

            return $hasAny ? '0:00' : '';
        }
        return sprintf('%d:%02d', intdiv($m, 60), $m % 60);
    }

    /** 実働（分） */
    public function getWorkedMinutesAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }
        $base = $this->start_time->diffInMinutes($this->end_time);
        return max(0, $base - $this->total_break_minutes);
    }

    /** 実働（H:MM） */
    public function getWorkedHmAttribute(): string
    {
        $m = $this->worked_minutes;
        return sprintf('%d:%02d', intdiv($m, 60), $m % 60);
    }

    /** 出勤（HH:MM） */
    public function getStartHmAttribute(): string
    {
        return $this->start_time instanceof Carbon ? $this->start_time->format('H:i') : '';
    }

    /** 退勤（HH:MM） */
    public function getEndHmAttribute(): string
    {
        return $this->end_time instanceof Carbon ? $this->end_time->format('H:i') : '';
    }

    // ===== 休憩同期（承認時などで使用） =====

    /**
     * 休憩を丸ごと入れ替え
     * $items 例: [['start' => '12:00', 'end' => '13:00'], ...]
     */
    public function syncBreaks(array $items): void
    {
        $this->breaks()->delete();

        foreach ($items as $b) {
            if (empty($b['start']) || empty($b['end'])) {
                continue; // 不正はスキップ（アーリーリターン）
            }

            $start = Carbon::parse($this->work_date . ' ' . $b['start']);
            $end   = Carbon::parse($this->work_date . ' ' . $b['end']);

            if ($end->lte($start)) {
                continue;
            }

            $this->breaks()->create([
                'break_start' => $start,
                'break_end'   => $end,
            ]);
        }
    }

    // ===== ルートキー（明示） =====

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
