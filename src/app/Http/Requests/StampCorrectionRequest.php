<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStampCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'        => ['required', 'string', 'max:200'],
            'type'          => ['required', 'string', Rule::in(['start','end','break'])],
            'attendance_id' => [
                'required',
                'integer',
                Rule::exists('attendances', 'id')
                    // 自分の勤怠に対する申請だけ許可する場合
                    ->where(fn ($q) => $q->where('user_id', $this->user()->id)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required'        => '修正理由を入力してください。',
            'reason.max'             => '修正理由は200文字以内で入力してください。',
            'type.required'          => '申請種別を選択してください。',
            'type.in'                => '申請種別が不正です。',
            'attendance_id.required' => '対象の勤怠が不正です。',
            'attendance_id.exists'   => '対象の勤怠が見つからないか、権限がありません。',
        ];
    }
}
