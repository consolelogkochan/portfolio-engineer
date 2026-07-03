<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'お名前を入力してください。',
            'name.max'         => 'お名前は100文字以内で入力してください。',
            'email.required'   => 'メールアドレスを入力してください。',
            'email.email'      => '有効なメールアドレスを入力してください。',
            'email.max'        => 'メールアドレスは255文字以内で入力してください。',
            'subject.required' => '件名を入力してください。',
            'subject.max'      => '件名は150文字以内で入力してください。',
            'message.required' => 'お問い合わせ内容を入力してください。',
            'message.max'      => 'お問い合わせ内容は2000文字以内で入力してください。',
        ];
    }
}
