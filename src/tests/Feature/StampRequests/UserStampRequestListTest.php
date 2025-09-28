<?php

namespace Tests\Feature\StampRequests;

use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

final class UserStampRequestListTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    // ─────────────────────────────────────────────────────────────
    // 一覧・詳細
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 申請一覧に自分の申請が表示される(): void
    {
        $me    = $this->createUser();
        $other = $this->createUser(['email' => 'other@example.com']);
        $this->actingAs($me);

        // 自分の勤怠＆申請
        $myAtt = Attendance::factory()->off(today())->create(['user_id' => $me->id]);
        $mine  = AttendanceCorrectRequest::factory()->pending()->create([
            'user_id'       => $me->id,
            'attendance_id' => $myAtt->id,
            'note'          => 'MY_NOTE_見えるはず',
        ]);

        // 他人の勤怠＆申請
        $otherAtt = Attendance::factory()->off(today())->create(['user_id' => $other->id]);
        AttendanceCorrectRequest::factory()->pending()->create([
            'user_id'       => $other->id,
            'attendance_id' => $otherAtt->id,
            'note'          => 'OTHER_NOTE_見えないはず',
        ]);

        $res = $this->get('/stamp-requests'); // StampCorrectionRequestController@index
        $res->assertOk();
        $res->assertSee('MY_NOTE_見えるはず');
        $res->assertSee((string) $mine->id);
        $res->assertDontSee('OTHER_NOTE_見えないはず');
    }

    #[Test]
    public function 申請詳細から勤怠詳細へ遷移できる(): void
    {
        $me = $this->createUser();
        $this->actingAs($me);

        $att = Attendance::factory()->off(today())->create(['user_id' => $me->id]);
        $req = AttendanceCorrectRequest::factory()->pending()->create([
            'user_id'       => $me->id,
            'attendance_id' => $att->id,
            'note'          => 'DETAIL_NOTE',
        ]);

        // 申請詳細にアクセスすると勤怠詳細にリダイレクト
        $this->get("/stamp-requests/{$req->id}")
            ->assertRedirect(route('attendance.show', $att->id));
    }

    #[Test]
    public function 勤怠詳細から申請一覧へ遷移できるリンクがある(): void
    {
        $me = $this->createUser();
        $this->actingAs($me);

        $att = Attendance::factory()->off(today())->create(['user_id' => $me->id]);

        $res = $this->get(route('attendance.show', $att));
        $res->assertOk();
        $res->assertSee('href="' . route('stamp_requests.index') . '"', false);
    }

    #[Test]
    public function 承認済みタブに自分の承認済み申請だけが表示される(): void
    {
        $me    = $this->createUser();
        $other = $this->createUser(['email' => 'o@example.com']);
        $this->actingAs($me);

        $attMe    = Attendance::factory()->off(today())->create(['user_id' => $me->id]);
        $attOther = Attendance::factory()->off(today())->create(['user_id' => $other->id]);

        AttendanceCorrectRequest::factory()->approved()->create([
            'user_id' => $me->id, 'attendance_id' => $attMe->id, 'note' => 'ME_OLD',
            'approved_at' => now()->subDay(),
        ]);
        AttendanceCorrectRequest::factory()->approved()->create([
            'user_id' => $me->id, 'attendance_id' => $attMe->id, 'note' => 'ME_NEW',
            'approved_at' => now(),
        ]);
        AttendanceCorrectRequest::factory()->approved()->create([
            'user_id' => $other->id, 'attendance_id' => $attOther->id, 'note' => 'OTHER_APPROVED',
        ]);

        $res = $this->get('/stamp-requests');
        $res->assertOk();
        $res->assertSee('ME_NEW');
        $res->assertSee('ME_OLD');
        $res->assertDontSee('OTHER_APPROVED');
    }

    // ─────────────────────────────────────────────────────────────
    // 作成（store）
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 修正申請が作成され_pendingになる(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $att = Attendance::factory()->working(today())->create(['user_id' => $user->id]);

        $res = $this->post(route('stamp_requests.store', $att), [
            'start_time' => '09:00',
            'end_time'   => '18:00',
            'breaks'     => [['start' => '12:00', 'end' => '13:00']],
            'note'       => '調整お願いします',
        ]);

        $res->assertRedirect(); // back()
        $this->assertDatabaseHas('attendance_correct_requests', [
            'attendance_id' => $att->id,
            'user_id'       => $user->id,
            'status'        => AttendanceCorrectRequest::STATUS_PENDING,
        ]);
    }

    #[Test]
    public function 同じ勤怠にpendingがあると二重申請できない(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $att  = Attendance::factory()->working(today())->create(['user_id' => $user->id]);

        AttendanceCorrectRequest::factory()->pending()->create([
            'user_id' => $user->id, 'attendance_id' => $att->id,
        ]);

        $res = $this->post(route('stamp_requests.store', $att), [
            'start_time' => '09:30',
            'note'       => '二重',
        ]);

        $res->assertSessionHas('error'); // コントローラのメッセージ
        $this->assertDatabaseCount('attendance_correct_requests', 1);
    }

    #[Test]
    public function 他人の勤怠への申請は403(): void
    {
        $me    = $this->createUser();
        $other = $this->createUser(['email' => 'x@example.com']);
        $this->actingAs($me);

        $othersAtt = Attendance::factory()->working(today())->create(['user_id' => $other->id]);

        $this->post(route('stamp_requests.store', $othersAtt), [
            'start_time' => '09:00',
            'note'       => 'NG',
        ])->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────
    // バリデーション
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function 備考未入力はバリデーションエラー(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $att = Attendance::factory()->working(today())->create(['user_id' => $user->id]);

        $res = $this->post(route('stamp_requests.store', $att), [
            'start_time' => '09:00',
            'end_time'   => '18:00',
            'breaks'     => [['start' => '12:00', 'end' => '13:00']],
            'note'       => '', // 未入力
        ]);

        $res->assertSessionHasErrors(['note']);
        $this->assertDatabaseMissing('attendance_correct_requests', [
            'attendance_id' => $att->id,
            'user_id'       => $user->id,
        ]);
    }

    #[Test]
    public function 出勤が退勤以降はエラー(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $att = Attendance::factory()->working(today())->create(['user_id' => $user->id]);

        $res = $this->post(route('stamp_requests.store', $att), [
            'start_time' => '19:00',
            'end_time'   => '18:00',
            'note'       => 'x',
        ]);

        $res->assertSessionHasErrors(['start_time']);
    }

    #[Test]
    public function 休憩の重複はエラー(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $att = Attendance::factory()->working(today())->create(['user_id' => $user->id]);

        // 12:00-13:00 と 12:30-12:45 が重複
        $res = $this->post(route('stamp_requests.store', $att), [
            'start_time' => '09:00',
            'end_time'   => '18:00',
            'breaks'     => [
                ['start' => '12:00', 'end' => '13:00'],
                ['start' => '12:30', 'end' => '12:45'],
            ],
            'note'       => '重複チェック',
        ]);

        $res->assertSessionHasErrors(); // withValidator の重複検出に引っかかる
    }

    // ─────────────────────────────────────────────────────────────
    // （任意）ページング分離
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function pendingとapprovedでページングクエリが分離して機能する(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $att = Attendance::factory()->off(today())->create(['user_id' => $user->id]);

        AttendanceCorrectRequest::factory()->count(12)->pending()->create([
            'user_id' => $user->id, 'attendance_id' => $att->id, 'note' => 'PENDING',
        ]);
        AttendanceCorrectRequest::factory()->count(12)->approved()->create([
            'user_id' => $user->id, 'attendance_id' => $att->id, 'note' => 'APPROVED',
        ]);

        // それぞれ 2ページ目にアクセスして 200 が返ること（分離パラメータで衝突しない）
        $this->get('/stamp-requests?pending_page=2')->assertOk();
        $this->get('/stamp-requests?approved_page=2')->assertOk();
    }
}
