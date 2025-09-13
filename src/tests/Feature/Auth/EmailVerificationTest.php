<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 会員登録後に認証メールが送られる(): void
    {
        Notification::fake();

        $res = $this->post(route('register.post'), [
            'name' => '一般 太郎',
            'email' => 'u@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Notification::assertSentTo(
            User::where('email', 'u@example.com')->first(),
            VerifyEmail::class
        );
    }

    /** @test */
    public function 誘導画面のボタンからメール認証サイトに遷移する_UIは簡易確認(): void
    {
        // 誘導ページのルート・ボタンテキストに依存するためリンク文言を assertSee で軽く確認
        $res = $this->get('/email/verify'); // Laravel 既定ページ/あなたのカスタムでもOK
        $res->assertOk();
        $res->assertSee('認証はこちらから'); // ボタン文言に合わせて調整
    }

    /** @test */
    public function 認証完了後_勤怠登録画面へ遷移する(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'u@example.com']);
        $url = \URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user);
        $res = $this->get($url);

        $res->assertRedirect('/attendance'); // 設計：メール認証後は勤怠登録画面へ
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
