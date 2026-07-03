<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

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
            subject: '【お問い合わせ】' . $this->contactSubject,
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
                'userName'    => $this->userName,
                'userEmail'   => $this->userEmail,
                'userMessage' => $this->userMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
