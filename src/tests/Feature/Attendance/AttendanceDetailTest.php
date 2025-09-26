<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
    public function 氏名がログインユーザー名になっている(): void
    {
        $user = $this->createUser(['name' => '表示 太郎']);
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-08-01',
            'start_time' => '2025-08-01 09:00:00',
            'end_time' => '2025-08-01 18:00:00',
        ]);

        $res = $this->get(route('attendance.show', $attendance));
        $res->assertOk();
        $res->assertSee('表示 太郎');
    }

    #[Test]
    public function 日付が選択した日付になっている(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-08-02',
        ]);

        $res = $this->get(route('attendance.show', $attendance));
        $res->assertOk();
        $res->assertSee('2025年');
        $res->assertSee('8月2日');
    }

    #[Test]
    public function 出勤退勤の表示が打刻と一致する(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2025-08-03',
            'start_time' => '2025-08-03 09:10:00',
            'end_time' => '2025-08-03 18:05:00',
        ]);

        $res = $this->get(route('attendance.show', $attendance));
        $res->assertOk();
        $res->assertSee('09:10');
        $res->assertSee('18:05');
    }

    #[Test]
    public function 休憩の表示が打刻と一致し_回数分プラス1行の入力欄がある(): void
    {
        $user = $this->createUser(['name' => '表示 太郎']);
        $this->actingAs($user);

        // 勤怠（対象日）
        $attendance = \App\Models\Attendance::factory()->create([
            'user_id'   => $user->id,
            'work_date' => '2025-08-03',
            'start_time'=> '2025-08-03 09:00:00',
            'end_time'  => '2025-08-03 18:00:00',
        ]);

        \App\Models\BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => '2025-08-03 10:00:00',
            'break_end'     => '2025-08-03 10:15:00',
        ]);
        \App\Models\BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => '2025-08-03 13:00:00',
            'break_end'     => '2025-08-03 13:10:00',
        ]);

        $res = $this->get(route('attendance.show', $attendance));
        $res->assertOk();

        $res->assertSee('10:00');
        $res->assertSee('10:15');
        $res->assertSee('13:00');
        $res->assertSee('13:10');

        $res->assertSee('name="breaks[0][start]"', false);
        $res->assertSee('name="breaks[0][end]"', false);
        $res->assertSee('name="breaks[1][start]"', false);
        $res->assertSee('name="breaks[1][end]"', false);
        // 追加の空行（+1行）
        $res->assertSee('name="breaks[2][start]"', false);
        $res->assertSee('name="breaks[2][end]"', false);
    }
}
