<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
    public function 当日勤怠が一覧表示される(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        Attendance::factory()->count(2)->create([
            'work_date' => Carbon::today()->toDateString(),
        ]);

        $res = $this->get('/admin/attendance/list');
        $res->assertOk();
        $res->assertSee((string)Carbon::today()->format('Y-m-d'));
    }

    #[Test]
    public function 前日ボタンで前日が表示される(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $res = $this->get('/admin/attendance/list?date=' . $yesterday);
        $res->assertOk();
        $res->assertSee($yesterday);
    }

    #[Test]
    public function 翌日ボタンで翌日が表示される(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $res = $this->get('/admin/attendance/list?date=' . $tomorrow);
        $res->assertOk();
        $res->assertSee($tomorrow);
    }
}
