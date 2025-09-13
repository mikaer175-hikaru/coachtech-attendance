<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 名前が未入力の場合_バリデーションメッセージが表示される(): void
    {
        $res = $this->post(route('register.post'), [
            'name' => '',
            'email' => 'u@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertSessionHasErrors(['name']);
        $this->assertStringContainsString('お名前を入力してください', session('errors')->first('name'));
    }

    /** @test */
    public function メールアドレスが未入力の場合_バリデーションメッセージが表示される(): void
    {
        $res = $this->post(route('register.post'), [
            'name' => '一般 太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('メールアドレスを入力してください', session('errors')->first('email'));
    }

    /** @test */
    public function パスワードが8文字未満の場合_バリデーションメッセージが表示される(): void
    {
        $res = $this->post(route('register.post'), [
            'name' => '一般 太郎',
            'email' => 'u@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertStringContainsString('パスワードは8文字以上で入力してください', session('errors')->first('password'));
    }

    /** @test */
    public function パスワードが一致しない場合_バリデーションメッセージが表示される(): void
    {
        $res = $this->post(route('register.post'), [
            'name' => '一般 太郎',
            'email' => 'u@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password999',
        ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertStringContainsString('パスワードと一致しません', session('errors')->first('password'));
    }

    /** @test */
    public function パスワードが未入力の場合_バリデーションメッセージが表示される(): void
    {
        $res = $this->post(route('register.post'), [
            'name' => '一般 太郎',
            'email' => 'u@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertStringContainsString('パスワードを入力してください', session('errors')->first('password'));
    }

    /** @test */
    public function 正しい入力で_ユーザーが保存される(): void
    {
        $res = $this->post(route('register.post'), [
            'name' => '一般 太郎',
            'email' => 'u@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $res->assertRedirect(route('login')); // 設計どおり
        $this->assertDatabaseHas('users', ['email' => 'u@example.com', 'name' => '一般 太郎']);
    }
}
