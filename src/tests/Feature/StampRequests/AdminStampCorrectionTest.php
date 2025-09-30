<?php

namespace Tests\Feature\StampRequests;

use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class AdminStampCorrectionTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    // ─────────────────────────────────────────────────────────────
    // 一覧（承認待ち / 承認済）
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 承認待ち一覧に全ユーザーの申請が表示される(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $u1 = $this->createUser(['email' => 'u1@example.com']);
        $u2 = $this->createUser(['email' => 'u2@example.com']);

        $a1 = Attendance::factory()->off(today())->create(['user_id' => $u1->id]);
        $a2 = Attendance::factory()->off(today())->create(['user_id' => $u2->id]);

        AttendanceCorrectRequest::factory()->pending()->create([
            'user_id' => $u1->id, 'attendance_id' => $a1->id, 'note' => 'PENDING_1',
        ]);
        AttendanceCorrectRequest::factory()->pending()->create([
            'user_id' => $u2->id, 'attendance_id' => $a2->id, 'note' => 'PENDING_2',
        ]);
        AttendanceCorrectRequest::factory()->approved()->create([
            'user_id' => $u2->id, 'attendance_id' => $a2->id, 'note' => 'APPROVED_SHOULD_NOT_APPEAR',
        ]);

        $res = $this->get(route('stamp_requests.index', ['tab' => 'pending']));
        $res->assertOk();
        $res->assertSee('PENDING_1');
        $res->assertSee('PENDING_2');
        $res->assertDontSee('APPROVED_SHOULD_NOT_APPEAR');
    }

    #[Test]
    public function 承認済み一覧に承認済み申請のみ表示され_承認日時の降順で並ぶ(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $u = $this->createUser();

        $a = Attendance::factory()->off(today())->create(['user_id' => $u->id]);

        AttendanceCorrectRequest::factory()->approved()->create([
            'user_id' => $u->id, 'attendance_id' => $a->id, 'note' => 'OLD',
            'approved_at' => now()->subDay(),
        ]);
        AttendanceCorrectRequest::factory()->approved()->create([
            'user_id' => $u->id, 'attendance_id' => $a->id, 'note' => 'NEW',
            'approved_at' => now(),
        ]);
        AttendanceCorrectRequest::factory()->pending()->create([
            'user_id' => $u->id, 'attendance_id' => $a->id, 'note' => 'PENDING_SHOULD_NOT_APPEAR',
        ]);

        $res = $this->get(route('stamp_requests.index', ['tab' => 'approved']));
        $res->assertOk();
        $content = $res->getContent();
        $res->assertSee('NEW');
        $res->assertSee('OLD');
        $res->assertDontSee('PENDING_SHOULD_NOT_APPEAR');

        // 並び順（NEW が OLD より前に出現）
        $this->assertTrue(strpos($content, 'NEW') < strpos($content, 'OLD'));
    }

    // ─────────────────────────────────────────────────────────────
    // 詳細
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 詳細画面に申請内容が表示される(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $u = $this->createUser();
        $att = Attendance::factory()->off(today())->create(['user_id' => $u->id]);

        $req = AttendanceCorrectRequest::factory()->pending()->create([
            'user_id' => $u->id, 'attendance_id' => $att->id, 'note' => 'DETAIL_NOTE',
        ]);

        $res = $this->get(route('admin.stamp_requests.show', $req));
        $res->assertOk();
        $res->assertSee('DETAIL_NOTE');
        $res->assertSee((string)$req->id);
    }

    // ─────────────────────────────────────────────────────────────
    // 承認
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 承認で申請がapprovedになり_勤怠の該当フィールドに反映される(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $u = $this->createUser();

        $att = Attendance::factory()->off(today())->create([
            'user_id'    => $u->id,
            'start_time' => today()->setTime(9, 0),
            'end_time'   => today()->setTime(18, 0),
        ]);

        $req = AttendanceCorrectRequest::factory()->pending()->create([
            'user_id'        => $u->id,
            'attendance_id'  => $att->id,
            'new_start_time' => today()->setTime(9, 30),
            'new_end_time'   => today()->setTime(18, 30),
            'note'           => '承認して反映',
        ]);

        $res = $this->post(route('admin.stamp_requests.approve', $req));
        $res->assertRedirect(); // 成功時はどこかにリダイレクト

        $this->assertDatabaseHas('attendance_correct_requests', [
            'id' => $req->id,
            'status' => AttendanceCorrectRequest::STATUS_APPROVED,
        ]);
        $this->assertNotNull($req->fresh()->approved_at);

        $att->refresh();
        $this->assertSame('09:30', optional($att->start_time)->format('H:i'));
        $this->assertSame('18:30', optional($att->end_time)->format('H:i'));
    }

    #[Test]
    public function 既にapprovedな申請は二重承認できず422が返る想定(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $u = $this->createUser();
        $att = Attendance::factory()->off(today())->create(['user_id' => $u->id]);
        $req = AttendanceCorrectRequest::factory()->approved()->create([
            'user_id' => $u->id, 'attendance_id' => $att->id,
        ]);

        $this->post(route('admin.stamp_requests.approve', $req))
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────
    // 却下
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 却下で申請がrejectedになり_勤怠は変更されない(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $u = $this->createUser();

        $att = Attendance::factory()->off(today())->create([
            'user_id'    => $u->id,
            'start_time' => today()->setTime(9, 0),
            'end_time'   => today()->setTime(18, 0),
        ]);

        $req = AttendanceCorrectRequest::factory()->pending()->create([
            'user_id'        => $u->id,
            'attendance_id'  => $att->id,
            'new_start_time' => today()->setTime(10, 0),
            'new_end_time'   => today()->setTime(19, 0),
            'note'           => '却下して反映なし',
        ]);

        $res = $this->post(route('admin.stamp_requests.reject', $req));
        $res->assertRedirect();

        $this->assertDatabaseHas('attendance_correct_requests', [
            'id' => $req->id,
            'status' => AttendanceCorrectRequest::STATUS_REJECTED,
        ]);
        $this->assertNotNull($req->fresh()->rejected_at);

        $att->refresh();
        // 却下なら変更されない前提
        $this->assertSame('09:00', optional($att->start_time)->format('H:i'));
        $this->assertSame('18:00', optional($att->end_time)->format('H:i'));
    }

    // ─────────────────────────────────────────────────────────────
    // 権限
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 一般ユーザーは承認や却下を実行できない(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $att = Attendance::factory()->off(today())->create(['user_id' => $user->id]);
        $req = AttendanceCorrectRequest::factory()->pending()->create([
            'user_id' => $user->id, 'attendance_id' => $att->id,
        ]);

        $this->post(route('admin.stamp_requests.approve', $req))->assertForbidden();
        $this->post(route('admin.stamp_requests.reject',  $req))->assertForbidden();
    }
}
