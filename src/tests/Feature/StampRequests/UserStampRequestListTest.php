<?php

namespace Tests\Feature\StampRequests;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;

class UserStampRequestListTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    /** @test */
    public function 申請一覧に自分の申請が表示される_データ作法確定後に実装(): void
    {
        $this->markTestSkipped('stamp_correction_requests のスキーマ確定後に実装');
    }

    /** @test */
    public function 申請詳細から勤怠詳細へ遷移する_リンク存在のみ検証(): void
    {
        $this->markTestSkipped('リレーションとルート（stamp_requests.store等）確定後に実装');
    }
}
