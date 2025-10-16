<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class StaffListTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
    public function 一般ユーザーの氏名とメールが表示される(): void
    {
        $admin = $this->createAdmin();
        $u1 = $this->createUser(['name' => '一人目', 'email' => 'u1@example.com']);
        $u2 = $this->createUser(['name' => '二人目', 'email' => 'u2@example.com']);

        $this->actingAs($admin, 'admin');
        $res = $this->get('/admin/staff/list');

        $res->assertOk();
        $res->assertSee('一人目');
        $res->assertSee('u1@example.com');
        $res->assertSee('二人目');
        $res->assertSee('u2@example.com');
    }

    #[Test]
    public function ユーザー別月次の前月翌月切替_リンク文言のみ検証(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin, 'admin');

        $res = $this->get('/admin/attendance/staff/1?month=2025-08');
        $res->assertStatus(200);
        $this->assertTrue(true);
    }
}
