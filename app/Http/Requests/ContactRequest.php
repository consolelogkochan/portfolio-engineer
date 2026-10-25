<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SpamReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
            // website・form_token はバリデーションルールを持たない（ユーザーに見せない）。
            // スパム判定は detectSpam() で行い、コントローラが分岐する。
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください。',
            'name.max' => 'お名前は100文字以内で入力してください。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'subject.required' => '件名を入力してください。',
            'subject.max' => '件名は150文字以内で入力してください。',
            'message.required' => 'お問い合わせ内容を入力してください。',
            'message.max' => 'お問い合わせ内容は2000文字以内で入力してください。',
        ];
    }

    /**
     * 中身ベースのスパム判定（3層）。
     * スパムなら理由、正常なら null を返す。
     * コントローラは、ボットの特徴（ハニーポット・時間トラップ）は静かに弾き、
     * 人間が掛かりうる層（トークン・URL数）はエラーを返す（SpamReason::isSilent()）。
     *
     * @return SpamReason|null スパム理由、または null（正常）
     */
    public function detectSpam(): ?SpamReason
    {
        // 第1層：ハニーポット（人間には見えない囮フィールドが埋まっていたらボット）
        if ($this->filled('website')) {
            return SpamReason::Honeypot;
        }

        // 第2層：時間トラップ（フォーム表示から送信までが短すぎたらボット）
        $token = $this->input('form_token', '');
        try {
            $issuedAt = (int) Crypt::decryptString($token);
            if ((time() - $issuedAt) < config('spam.min_seconds')) {
                return SpamReason::TooFast;
            }
        } catch (Throwable) {
            // 復号失敗＝トークン改ざん or 欠落
            return SpamReason::InvalidToken;
        }

        // 第3層：URL 数（スパムリンクの大量投稿を弾く）
        $urlCount = preg_match_all('/https?:\/\/\S+/i', $this->input('message', ''));
        if ($urlCount > config('spam.max_urls')) {
            return SpamReason::TooManyUrls;
        }

        return null;
    }
}
