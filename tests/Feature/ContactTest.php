<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 問い合わせフォームのスパム判定（ContactRequest::detectSpam）の検証。
 *
 * 7-5c-1で、第1層（ハニーポット）が input('website', '') !== '' という
 * 値の比較で、ConvertEmptyStringsToNullミドルウェアが空文字列をnullに変換する
 * ため、実際のフォーム（websiteキーを常に空文字列で送る）が必ず判定に掛かって
 * いたバグを修正した。テスト1がこのバグそのものを検出する。
 */
class ContactTest extends TestCase
{
    /** 経過秒数の判定を確実に通過させるため、config('spam.min_seconds')より十分前の時刻にする */
    private function validFormToken(): string
    {
        return Crypt::encryptString((string) (time() - 10));
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'subject' => 'お問い合わせ件名',
            'message' => 'お問い合わせ本文です。',
            'website' => '',
            'form_token' => $this->validFormToken(),
        ], $overrides);
    }

    /**
     * 実際のフォームが送る形（websiteキーは存在し、値は空文字列）。
     * これが今回のバグを検出するテストである。
     */
    public function test_website_as_empty_string_sends_mail(): void
    {
        Mail::fake();

        $response = $this->post('/contact', $this->validPayload());

        Mail::assertSent(ContactMail::class);
        $response->assertSessionHas('success');
    }

    /** websiteに値が入っている場合は、ボットとして静かに捨てる（メール送信なし、応答は成功） */
    public function test_website_filled_does_not_send_mail(): void
    {
        Mail::fake();

        $response = $this->post('/contact', $this->validPayload([
            'website' => 'https://bot.example.com',
        ]));

        Mail::assertNothingSent();
        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();
    }

    /** websiteキー自体を送らない場合も、既定値''により正常に送信される */
    public function test_website_key_omitted_sends_mail(): void
    {
        Mail::fake();

        $payload = $this->validPayload();
        unset($payload['website']);

        $response = $this->post('/contact', $payload);

        Mail::assertSent(ContactMail::class);
        $response->assertSessionHas('success');
    }

    /** 本文にURLが3つ（既定の上限2を超える）あると、メールを送らずエラーを返す */
    public function test_too_many_urls_does_not_send_mail_and_returns_error(): void
    {
        Mail::fake();

        $response = $this->post('/contact', $this->validPayload([
            'message' => 'http://a.example.com http://b.example.com http://c.example.com',
        ]));

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('message');
        $response->assertSessionDoesntHaveErrors(['name', 'email', 'subject']);
    }

    /** form_tokenが復号できない場合も、メールを送らずエラーを返す */
    public function test_invalid_form_token_does_not_send_mail_and_returns_error(): void
    {
        Mail::fake();

        $response = $this->post('/contact', $this->validPayload([
            'form_token' => 'not-a-valid-encrypted-token',
        ]));

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('message');
    }
}
