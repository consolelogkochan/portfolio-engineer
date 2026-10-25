<?php

declare(strict_types=1);

namespace App\Mail;

use App\Services\UntrustedMarkdownRenderer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\HtmlString;

class ContactMail extends Mailable
{
    public function __construct(
        public readonly string $userName,
        public readonly string $userEmail,
        // Mailable 基底クラスに $subject が存在するため contactSubject で区別する
        public readonly string $contactSubject,
        public readonly string $userMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            // 件名はフレームワークに渡す（手動文字列結合でヘッダを作らない）。
            // ユーザー入力をヘッダに直接連結するとメールヘッダインジェクションのリスクがあるため、
            // Laravel の Envelope が内部でエスケープ処理を行う。
            subject: '【お問い合わせ】'.$this->contactSubject,
            // ユーザーのメールアドレスは Reply-To に置く。
            // From に置くと送信元詐称になるため厳禁。
            replyTo: [new Address($this->userEmail, $this->userName)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact',
            with: [
                'userName' => $this->userName,
                'userEmail' => $this->userEmail,
                'userMessage' => $this->userMessage,
                'userMessageHtml' => new HtmlString($this->userMessageHtml()),
            ],
        );
    }

    /**
     * 訪問者の本文を、リンク・画像・生の HTML を落とした HTML にする（UntrustedMarkdownRenderer）。
     *
     * Markdown のメールは、テンプレートを展開した全体をもう一度 Markdown として変換する
     * （Laravel のメールのレイアウトの Markdown::parse）。本文をそのまま置くと、訪問者の書いた
     * Markdown がそこで解釈される。変換済みの HTML を置いても、HTML のかたまりは空行で終わり、
     * その後ろは Markdown として読まれる（コードブロックの中の空行など）。
     * そのため改行を文字参照（&#10;）にして1行にし、全体を1つの HTML のかたまりにする。
     * 文字参照の改行は、<pre> の中では改行として、そのほかでは空白として表示される。
     */
    private function userMessageHtml(): string
    {
        $html = app(UntrustedMarkdownRenderer::class)->toHtml($this->userMessage);

        return str_replace(["\r\n", "\r", "\n"], '&#10;', $html);
    }

    public function attachments(): array
    {
        return [];
    }
}
