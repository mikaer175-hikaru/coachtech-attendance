<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class ClockUiTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
    public function 画面に現在日時が表示される(): void
    {
        $this->travelTo('2025-08-30 09:10:00');
        $this->freezeTime();

        $user = $this->createUser();
        $this->actingAs($user);

        $res = $this->get('/attendance');
        $res->assertOk();
        $res->assertSee('2025年8月30日');
        $res->assertSee('09:10');

        $this->travelBack();
    }

    #[Test]
    public function 勤務外ステータスが表示される(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $res = $this->get('/attendance');
        $res->assertOk();
        $res->assertSee('勤務外');
    }

    #[Test]
    public function 出勤中ステータスが表示される(): void
    {
        $this->travelTo('2025-08-30 10:00:00');
        $this->freezeTime();

        $user = $this->createUser();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id'    => $user->id,
            'work_date'  => Carbon::today()->toDateString(),
            'start_time' => Carbon::parse('2025-08-30 09:00:00'),
            'end_time'   => null,
        ]);

        $res = $this->get('/attendance');
        $res->assertOk();
        $res->assertSee('出勤中');

        $this->travelBack();
    }

    #[Test]
    public function 休憩中ステータスが表示される_後で実装(): void
    {
        $this->markTestSkipped('休憩の内部判定仕様確定後に実装');
    }

    #[Test]
    public function 退勤済ステータスが表示される(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Attendance::factory()->create([
            'user_id'    => $user->id,
            'work_date'  => now()->toDateString(),
            'start_time' => now()->subHours(9),
            'end_time'   => now()->subHour(),
        ]);

        $res = $this->get('/attendance');
        $res->assertOk();
        $res->assertSee('退勤済');
    }
}
