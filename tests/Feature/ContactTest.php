<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ContactMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * 問い合わせフォームのスパム判定（ContactRequest::detectSpam）の検証。
 *
 * 7-5c-1で、第1層（ハニーポット）が input('website', '') !== '' という
 * 値の比較で、ConvertEmptyStringsToNullミドルウェアが空文字列をnullに変換する
 * ため、実際のフォーム（websiteキーを常に空文字列で送る）が必ず判定に掛かって
 * いたバグを修正した。テスト1がこのバグそのものを検出する。
 */
// このテストは、1つのメソッドで1回だけPOSTしている。
// 問い合わせのルートには throttle:3,1 が付いているため、
// 1つのメソッドで4回以上POSTすると429で落ちる。
// メソッドをまたいだ蓄積は起きない（キャッシュがテストごとに空から始まるため。
// 実測 2026-09-26）。
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
        $response->assertSessionHasErrors('general');
        $response->assertSessionDoesntHaveErrors(['name', 'email', 'subject', 'message']);
    }

    /** form_tokenが復号できない場合も、メールを送らずエラーを返す */
    public function test_invalid_form_token_does_not_send_mail_and_returns_error(): void
    {
        Mail::fake();

        $response = $this->post('/contact', $this->validPayload([
            'form_token' => 'not-a-valid-encrypted-token',
        ]));

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('general');
    }

    /** メール送信で例外（Symfony MailerのTransportExceptionInterface）が投げられた場合、
     *  500にせず、汎用エラー枠（errors.general）で返し、入力内容を保持する */
    public function test_mail_send_failure_returns_generic_error_and_preserves_input(): void
    {
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(
            new TransportException('simulated failure'),
        );

        $payload = $this->validPayload();
        $response = $this->post('/contact', $payload);

        $response->assertSessionHasErrors('general');
        $response->assertSessionDoesntHaveErrors(['name', 'email', 'subject', 'message']);
        $response->assertSessionHasInput('name', $payload['name']);
        $response->assertSessionHasInput('email', $payload['email']);
        $response->assertSessionHasInput('subject', $payload['subject']);
        $response->assertSessionHasInput('message', $payload['message']);
    }

    /** メール送信失敗時、errorレベルでログに残す。入力内容は記録しない */
    public function test_mail_send_failure_logs_at_error_level(): void
    {
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(
            new TransportException('simulated failure'),
        );

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Contact mail send failed'
                    && $context === [
                        'exception' => TransportException::class,
                        'message' => 'simulated failure',
                        'code' => 0,
                    ];
            });

        $this->post('/contact', $this->validPayload());
    }
}
