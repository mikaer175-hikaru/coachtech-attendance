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
        $this->actingAs($admin, 'admin');

        $attendance = Attendance::factory()->create();

        $showUrl   = route('admin.attendance.show', $attendance);
        $updateUrl = route('admin.attendance.update', $attendance);

        $res = $this->from($showUrl)->patch($updateUrl, [
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
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $attendance = Attendance::factory()->create([
            'start_time' => '09:00',
            'end_time'   => '10:00',
        ]);

        $showUrl   = route('admin.attendance.show',   $attendance);
        $updateUrl = route('admin.attendance.update', $attendance);

        $res = $this->from($showUrl)->patch($updateUrl, [
            'start_time'       => '09:00',
            'end_time'         => '10:00',
            'break_end_time'   => '11:00',
            'note'             => 'メモ',
        ]);

        $res->assertSessionHasErrors(['break_end_time']);
        $this->assertSame(
            '休憩時間もしくは退勤時間が不適切な値です',
            session('errors')->first('break_end_time')
        );
    }

    #[Test]
    public function 備考未入力で_バリデーションメッセージ(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $attendance = Attendance::factory()->create([
            'start_time' => '09:00',
            'end_time'   => '18:00',
        ]);

        $showUrl   = route('admin.attendance.show', $attendance);
        $updateUrl = route('admin.attendance.update', $attendance);

        $res = $this->from($showUrl)->patch($updateUrl, [
            'start_time' => '09:00',
            'end_time'   => '18:00',
            'note'       => '',
        ]);

        $res->assertSessionHasErrors(['note']);
        $this->assertStringContainsString('備考を記入してください', session('errors')->first('note'));
    }
}