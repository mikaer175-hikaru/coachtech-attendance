<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class AdminAttendanceDetailValidationTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
    public function 出勤時間退勤時間の逆転で_バリデーションメッセージ(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create();

        $res = $this->from("/attendance/{$attendance->id}")
            ->put("/attendance/{$attendance->id}", [
                'start_time' => '19:00',
                'end_time'   => '09:00',
                'note'       => 'メモ',
            ]);

        $res->assertSessionHasErrors();
        $this->assertStringContainsString(
            '出勤時間もしくは退勤時間が不適切な値です',
            collect(session('errors')->all())->join(' ')
        );
    }

    #[Test]
    public function 休憩時間が退勤より後で_バリデーションメッセージ(): void
    {
        $this->markTestSkipped('休憩カラム確定後に追加（「休憩時間が不適切な値です」想定）');
    }

    #[Test]
    public function 備考未入力で_バリデーションメッセージ(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $attendance = Attendance::factory()->create([
            'start_time' => '09:00',
            'end_time'   => '18:00',
        ]);

        $res = $this->from("/attendance/{$attendance->id}")
            ->put("/attendance/{$attendance->id}", [
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'note'       => '',
            ]);

        $res->assertSessionHasErrors(['note']);
        $this->assertStringContainsString('備考を記入してください', session('errors')->first('note'));
    }
}
