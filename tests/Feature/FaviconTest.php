<?php

declare(strict_types=1);

namespace Tests\Feature;

use DOMDocument;
use DOMElement;
use Tests\TestCase;

/**
 * サイトのアイコン（favicon の ICO・SVG と apple-touch-icon）の検証（7-7c）。
 *
 * link は全ページ共通のテンプレート（resources/views/app.blade.php）に置いている。
 * 複数のページで3つの link が出ていることと、指す先のファイルが public/ に実在し、
 * 中身があることを確かめる（参照の書き間違いや、空のファイルへの退行に CI で気づくため）。
 */
class FaviconTest extends TestCase
{
    /**
     * 期待する link：rel と href の組。
     *
     * @var array<int, array{rel: string, href: string}>
     */
    private const array EXPECTED_LINKS = [
        ['rel' => 'icon', 'href' => '/favicon.ico'],
        ['rel' => 'icon', 'href' => '/favicon-v1.svg'],
        ['rel' => 'apple-touch-icon', 'href' => '/apple-touch-icon.png'],
    ];

    /**
     * HTML から rel が icon または apple-touch-icon の link を取り出す。
     *
     * @return array<int, array{rel: string, href: string}>
     */
    private function iconLinks(string $html): array
    {
        $dom = new DOMDocument;
        // HTML5 の要素名に対する libxml の警告を出さない
        @$dom->loadHTML($html);

        $links = [];
        foreach ($dom->getElementsByTagName('link') as $link) {
            /** @var DOMElement $link */
            $rel = strtolower(trim($link->getAttribute('rel')));
            if (in_array($rel, ['icon', 'apple-touch-icon'], true)) {
                $links[] = ['rel' => $rel, 'href' => $link->getAttribute('href')];
            }
        }

        return $links;
    }

    public function test_icon_links_are_output_on_pages(): void
    {
        foreach (['/', '/works', '/works/portfolio-engineer', '/logs/portfolio-engineer', '/about', '/contact'] as $path) {
            $response = $this->get($path);
            $response->assertOk();

            $this->assertSame(
                self::EXPECTED_LINKS,
                $this->iconLinks((string) $response->getContent()),
                "{$path}: アイコンの link が期待どおりに出ていない",
            );
        }
    }

    public function test_icon_files_exist_and_are_not_empty(): void
    {
        foreach (self::EXPECTED_LINKS as $link) {
            $path = public_path(ltrim($link['href'], '/'));
            $this->assertFileExists($path, "{$link['href']} が public/ に存在しない");
            $this->assertGreaterThan(0, filesize($path), "{$link['href']} が空のファイルになっている");
        }
    }

    /**
     * favicon.ico に 16・32・48px の3つが入っていることを、ICO のヘッダから確かめる。
     * ヘッダは6バイト（予約・種類・枚数）、続いて1枚あたり16バイトの目録（先頭の2バイトが幅と高さ）。
     */
    public function test_favicon_ico_contains_16_32_48(): void
    {
        $data = (string) file_get_contents(public_path('favicon.ico'));
        $header = unpack('vreserved/vtype/vcount', substr($data, 0, 6));
        $this->assertSame(1, $header['type'], 'ICO の種類（1＝アイコン）ではない');

        $sizes = [];
        for ($i = 0; $i < $header['count']; $i++) {
            $entry = unpack('Cwidth/Cheight', substr($data, 6 + $i * 16, 2));
            $sizes[] = $entry['width'].'x'.$entry['height'];
        }
        sort($sizes, SORT_NATURAL);

        $this->assertSame(['16x16', '32x32', '48x48'], $sizes);
    }

    public function test_apple_touch_icon_is_180_png(): void
    {
        $info = getimagesize(public_path('apple-touch-icon.png'));
        $this->assertNotFalse($info);
        $this->assertSame([180, 180, 'image/png'], [$info[0], $info[1], $info['mime']]);
    }

    /**
     * SVG の「K」は字形を図形（path）に直して描いている。favicon として描かれるときはページの
     * フォントが読み込まれず、text だと閲覧者の環境のフォントで描かれうる（未確認の前提）ため、
     * text が入っていないことを確かめる。
     */
    public function test_svg_icon_has_no_text_element(): void
    {
        $svg = (string) file_get_contents(public_path('favicon-v1.svg'));
        $this->assertStringContainsString('<path', $svg);
        $this->assertStringNotContainsString('<text', $svg);
    }
}
