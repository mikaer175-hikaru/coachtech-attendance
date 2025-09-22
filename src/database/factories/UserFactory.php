<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * このファクトリが対象とするモデル
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password123'),
            'remember_token'    => Str::random(10),
            'is_first_login'    => true,
            'is_admin'          => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** 管理者ユーザー */
    public function admin(): static
    {
        return $this->state(fn () => [
            'is_admin'       => true,
            'is_first_login' => false,
        ]);
    }

    /** 一般ユーザー */
    public function general(): static
    {
        return $this->state(fn () => [
            'is_admin'       => false,
            'is_first_login' => true,
        ]);
    }
}
