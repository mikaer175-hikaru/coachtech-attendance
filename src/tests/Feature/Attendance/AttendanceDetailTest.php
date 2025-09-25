<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        $res = $this->get("/attendance/{$attendance->id}");
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

        $res = $this->get("/attendance/{$attendance->id}");
        $res->assertOk();
        $res->assertSee('2025-08-02');
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

        $res = $this->get("/attendance/{$attendance->id}");
        $res->assertOk();
        $res->assertSee('09:10');
        $res->assertSee('18:05');
    }

    #[Test]
    public function 休憩の表示が打刻と一致する_内部仕様確定後に追記(): void
    {
        $this->markTestSkipped('休憩の保持方法確定後に、休憩の表示検証を追加');
    }
}
