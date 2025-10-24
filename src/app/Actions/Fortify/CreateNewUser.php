<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateNewUser
{
    public function create(array $input)
    {
        Validator::make(
            $input,
            [
                'name'                  => ['required', 'string', 'max:255'],
                'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password'              => ['required', 'string', 'min:8', 'confirmed'],
                'password_confirmation' => ['required', 'string', 'min:8'],
            ],
            [
                'name.required'                  => 'お名前を入力してください',
                'email.required'                 => 'メールアドレスを入力してください',
                'email.email'                    => 'メールアドレスは「ユーザー名@ドメイン」形式で入力してください',
                'email.unique'                   => 'すでに登録済みのメールアドレスです',
                'password.required'              => 'パスワードを入力してください',
                'password.min'                   => 'パスワードは8文字以上で入力してください',
                'password.confirmed'             => 'パスワードと一致しません',
                'password_confirmation.required' => 'パスワード確認を入力してください',
                'password_confirmation.min'      => 'パスワード確認は8文字以上で入力してください',
            ]
        )->validate();

        return User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}

