<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Support\CreatesUsers;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase, CreatesUsers;

    #[Test]
    public function 管理者_メール未入力(): void
    {
        $this->createAdmin();

        $res = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('メールアドレスを入力してください', session('errors')->first('email'));
    }

    #[Test]
    public function 管理者_パスワード未入力(): void
    {
        $this->createAdmin();

        $res = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertStringContainsString('パスワードを入力してください', session('errors')->first('password'));
    }

    #[Test]
    public function 管理者_不一致の資格情報(): void
    {
        $this->createAdmin();

        $res = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $res->assertRedirect('/admin/login');
        $res->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('メールアドレスまたはパスワードが正しくありません。', session('errors')->first('email'));
    }
}
