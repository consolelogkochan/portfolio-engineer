<?php

declare(strict_types=1);

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Markdownを本文HTMLに変換するサービス。
 *
 * 本サービスは著者が管理する信頼済みコンテンツ専用。
 * ユーザー入力等の外部・未検証データには使用しないこと（html_input=allow のため）。
 */
class MarkdownRenderer
{
    private readonly MarkdownConverter $converter;

    public function __construct()
    {
        // CommonMarkCoreExtension を明示登録する Environment 方式。
        // 将来の拡張（脚注・テーブル等）は addExtension() で追加できる。
        $environment = new Environment([
            'html_input'         => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /** Markdown文字列を受け取りHTML文字列を返す */
    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
