<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
    public function メールが未入力で_バリデーションメッセージ(): void
    {
        $this->createUser();

        $res = $this->post(route('login.submit'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('メールアドレスを入力してください', session('errors')->first('email'));
    }

    #[Test]
    public function パスワードが未入力で_バリデーションメッセージ(): void
    {
        $this->createUser();

        $res = $this->post(route('login.submit'), [
            'email' => 'user@example.com',
            'password' => '',
        ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertStringContainsString('パスワードを入力してください', session('errors')->first('password'));
    }

    #[Test]
    public function 不一致の資格情報で_アラートメッセージ(): void
    {
        $this->createUser();

        $res = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $res->assertRedirect(route('login'));
        $res->assertSessionHas('error', 'ログイン情報が登録されていません');
    }
}
