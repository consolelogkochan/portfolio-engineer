<?php

declare(strict_types=1);

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * 運営者以外が書いた Markdown（問い合わせの本文）を HTML に変換するサービス。
 *
 * 見出し・表・リストなどの見た目は活かし、表示と行き先を別々に書ける要素だけを落とす。
 * - 生の HTML は取り除く（html_input=strip。タグの外の文字は残る）
 * - リンクは行き先を落とし、表示の文字だけを残す（<https://…> の形も同じ）
 * - 画像は読み込まず、代わりの文字（alt）だけを残す
 * 素の URL はリンクにしない（自動でリンクにする拡張を入れていない）。
 *
 * 著者が書く本文には MarkdownRenderer を使う。
 */
class UntrustedMarkdownRenderer
{
    private readonly MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        // 問い合わせのメールの側（Laravel の Markdown のメール）と同じく、表を使えるようにする
        $environment->addExtension(new TableExtension);

        // 既定の描き方より優先して、リンクと画像を「中の文字だけ」で描く
        $textOnly = new class implements NodeRendererInterface
        {
            public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
            {
                return $childRenderer->renderNodes($node->children());
            }
        };
        $environment->addRenderer(Link::class, $textOnly, 100);
        $environment->addRenderer(Image::class, $textOnly, 100);

        $this->converter = new MarkdownConverter($environment);
    }

    /** Markdown文字列を受け取りHTML文字列を返す */
    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
