<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 問い合わせのスパム判定（ContactRequest::detectSpam）の理由。
 * 値の文字列は、スパムと判定したときのログに残す（ContactController）。
 */
enum SpamReason: string
{
    case Honeypot = 'honeypot';
    case TooFast = 'too_fast';
    case InvalidToken = 'invalid_token';
    case TooManyUrls = 'too_many_urls';

    /**
     * ボットの特徴（ハニーポット・時間トラップ）は静かに弾く：ボットにはメールが送られたように見せ、
     * 実際には送らない。人間が現実に掛かりうる層（URL数・トークン破損）は、本人が気づいて
     * 送り直せるようにエラーを返す。
     */
    public function isSilent(): bool
    {
        return match ($this) {
            self::Honeypot, self::TooFast => true,
            self::InvalidToken, self::TooManyUrls => false,
        };
    }
}
