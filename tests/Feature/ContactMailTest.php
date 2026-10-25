<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\ContactMail;
use Tests\TestCase;

/**
 * 問い合わせのメール（HTML）で、訪問者の本文がどう描かれるかの検証。
 *
 * 見出し・表・リストは HTML になり、リンク・画像・生の HTML は取り除かれる
 * （UntrustedMarkdownRenderer。ContactMail::userMessageHtml の説明も参照）。
 * メールの枠（Laravel の既定のテーマ）にも href や src があるため、
 * 訪問者の本文の区画だけを取り出して確かめる。
 */
class ContactMailTest extends TestCase
{
    private function renderMail(string $message): string
    {
        return (new ContactMail('テスト太郎', 'test@example.com', '件名', $message))->render();
    }

    /** メールの HTML から、訪問者の本文の区画（div.visitor-message の中）を取り出す */
    private function visitorSection(string $html): string
    {
        $this->assertSame(1, preg_match('/<div class="visitor-message"[^>]*>(.*?)<\/div>\s*<hr/s', $html, $m));

        return $m[1];
    }

    public function test_headings_tables_and_lists_become_html(): void
    {
        $section = $this->visitorSection($this->renderMail(
            "# 見出し\n\n| 項目 | 値 |\n| --- | --- |\n| a | 1 |\n\n- 一つめ\n- 二つめ\n",
        ));

        $this->assertMatchesRegularExpression('/<h1[^>]*>見出し<\/h1>/', $section);
        $this->assertMatchesRegularExpression('/<table[^>]*>.*<th[^>]*>項目<\/th>.*<td[^>]*>a<\/td>/s', $section);
        $this->assertMatchesRegularExpression('/<ul[^>]*>\s*<li[^>]*>一つめ<\/li>\s*<li[^>]*>二つめ<\/li>/', $section);
    }

    public function test_links_images_and_raw_html_are_removed(): void
    {
        $html = $this->renderMail(implode("\n\n", [
            '[押して1](javascript:alert(1))',
            '[押して2](https://example.com)',
            '![画像の説明](https://example.com/a.png)',
            '<a href="https://evil.example/login">生のリンク</a>',
            '<img src=x onerror=alert(1)>',
            '<script>alert(1)</script>',
        ]));
        $section = $this->visitorSection($html);

        foreach (['href', 'src', 'onerror', '<script', '<a', '<img'] as $needle) {
            $this->assertStringNotContainsString($needle, $section);
        }
        // 行き先は、メール全体のどこにも出ない
        foreach (['javascript:', 'https://example.com', 'evil.example', 'alert(1)</script>'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
        // 表示の文字は残る
        foreach (['押して1', '押して2', '画像の説明', '生のリンク'] as $text) {
            $this->assertStringContainsString($text, $section);
        }
    }

    public function test_bare_url_is_not_turned_into_a_link(): void
    {
        $section = $this->visitorSection($this->renderMail('詳しくは https://example.com を見てください'));

        $this->assertStringContainsString('https://example.com', $section);
        $this->assertStringNotContainsString('<a', $section);
    }

    /**
     * メールの全体はもう一度 Markdown として変換される。空行で HTML のかたまりが切れると、
     * その後ろの訪問者の文字が、そこで Markdown として解釈されてしまう。
     */
    public function test_blank_line_in_code_block_does_not_let_markdown_through(): void
    {
        $section = $this->visitorSection($this->renderMail(
            "```\n一行目\n\n[押して](https://example.com)\n```\n",
        ));

        $this->assertStringContainsString('[押して](https://example.com)', $section);
        $this->assertStringNotContainsString('<a', $section);
    }
}
