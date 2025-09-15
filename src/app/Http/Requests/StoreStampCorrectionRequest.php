<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StampCorrectionRequest extends FormRequest
{
    public function authorize()
    {
        return true; // 認可チェックは別でやるならtrueでOK
    }

    public function rules()
    {
        return [
            'reason' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages()
    {
        return [
            'reason.required' => '修正理由を入力してください。',
            'reason.max' => '修正理由は200文字以内で入力してください。',
        ];
    }
}
