<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 問い合わせの件名と名前に改行が入っても、組み立てた後のメールのヘッダーが増えないことの検証。
 *
 * ContactTest は Mail::fake() で「送ったか」を見るため、メールを組み立てない。
 * ここでは送り方を array（phpunit.xml の MAIL_MAILER）のままにし、
 * フォームへの送信から始めて、組み立てた後の文字列を見る。
 * 検証の規則を通った後の値が、どう書き出されるかを確かめるため（7-9b）。
 *
 * コードを読んで得た結論（vendor の symfony/mime）：
 * 件名の改行は符号化される（UnstructuredHeader）。名前の改行は取り除かれる（Address）。
 * アドレスに改行があれば、検証の規則で弾かれる。
 */
// 1つのメソッドで1回だけPOSTする（問い合わせのルートは throttle:3,1。ContactTest と同じ）。
class ContactMailHeaderTest extends TestCase
{
    /** 経過秒数の判定を通過させるため、config('spam.min_seconds')より前の時刻にする */
    private function validFormToken(): string
    {
        return Crypt::encryptString((string) (time() - config('spam.min_seconds') - 1));
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

    /** array の送り方が受け取ったメールを、組み立てた後の文字列で返す。1通だけ送ったことも確かめる */
    private function sentMessageString(): string
    {
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);

        return $messages->first()->toString();
    }

    /** ヘッダーの部分だけを、行に分けて返す（本文との境目は最初の空行） */
    private function headerLines(string $raw): array
    {
        [$head] = explode("\r\n\r\n", $raw, 2);

        return explode("\r\n", $head);
    }

    /** 名前の行と、その続きの行（先頭が空白）をつないだ値を返す */
    private function headerValue(array $lines, string $name): string
    {
        $value = null;
        foreach ($lines as $line) {
            if ($value !== null && preg_match('/^[ \t]/', $line)) {
                $value .= $line;

                continue;
            }
            if ($value !== null) {
                break;
            }
            if (stripos($line, $name.':') === 0) {
                $value = substr($line, strlen($name) + 1);
            }
        }
        $this->assertNotNull($value, "{$name} のヘッダーが無い");

        return $value;
    }

    /** ヘッダーのどの行も「名前: 値」か、続きの行（先頭が空白）であること。Bcc の行が無いこと */
    private function assertNoInjectedHeader(array $lines): void
    {
        foreach ($lines as $line) {
            $this->assertMatchesRegularExpression('/^([A-Za-z0-9-]+: |[ \t])/', $line, "ヘッダーの形でない行がある：{$line}");
        }
        $this->assertSame(0, preg_match('/^Bcc:/mi', implode("\n", $lines)));
    }

    public function test_line_break_in_subject_is_encoded_and_adds_no_header(): void
    {
        $this->post('/contact', $this->validPayload([
            'subject' => "件名\r\nBcc: attacker@example.com",
        ]))->assertSessionHasNoErrors();

        $lines = $this->headerLines($this->sentMessageString());
        $this->assertNoInjectedHeader($lines);

        // 改行の文字そのものは Subject に出ず、符号化された語（=?utf-8?Q?…?=）の中に入る。
        // 符号化を戻すと、入力した文字列が件名の一部として残っている。
        $subject = $this->headerValue($lines, 'Subject');
        $this->assertStringNotContainsString("\r", $subject);
        $this->assertStringNotContainsString("\n", $subject);
        $this->assertStringContainsString('Bcc: attacker@example.com', iconv_mime_decode($subject, 0, 'UTF-8'));
    }

    public function test_line_break_in_name_is_removed_from_reply_to_and_adds_no_header(): void
    {
        $this->post('/contact', $this->validPayload([
            'name' => "テスト\r\nBcc: attacker@example.com",
        ]))->assertSessionHasNoErrors();

        $lines = $this->headerLines($this->sentMessageString());
        $this->assertNoInjectedHeader($lines);

        $replyTo = $this->headerValue($lines, 'Reply-To');
        $this->assertStringNotContainsString("\r", $replyTo);
        $this->assertStringNotContainsString("\n", $replyTo);
        $this->assertStringContainsString('<test@example.com>', $replyTo);
    }

    public function test_line_break_in_email_is_rejected_and_nothing_is_sent(): void
    {
        $this->post('/contact', $this->validPayload([
            'email' => "test@example.com\r\nBcc: attacker@example.com",
        ]))->assertSessionHasErrors('email');

        $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
    }
}
