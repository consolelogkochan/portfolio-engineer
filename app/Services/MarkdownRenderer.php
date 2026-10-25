<?php

declare(strict_types=1);

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Markdownを本文HTMLに変換するサービス。
 *
 * 本サービスは著者が管理する信頼済みコンテンツ専用（content/ の .md。Git で管理し、著者だけが書く）。
 * 変換した HTML は、作品詳細・開発ログ・About の画面で dangerouslySetInnerHTML によりそのまま出す。
 * それを許す根拠と、その前提が崩れる条件はこの説明に置く（各 Controller と Pages の TSX はここを指す）。
 *
 * どこまで守られているか：
 * - html_input=allow のため、本文に書いた生の HTML はそのまま出力される
 *   （<a href="javascript:…"> や onerror 属性も残る）。
 * - allow_unsafe_links=false が除くのは、Markdown の書き方のリンクと画像の危ない行き先
 *   （javascript:・vbscript:・file:・data:。data: は一部の画像を除く）だけ。生の HTML には効かない。
 * 著者以外が書く内容をこの仕組みで表示するようになったら、html_input を escape か strip にする。
 * 運営者以外が書いた Markdown（問い合わせの本文）には UntrustedMarkdownRenderer を使う。
 * この前提で維持する判断と、再検討の条件は docs/ledger.md の 41-7。
 */
class MarkdownRenderer
{
    private readonly MarkdownConverter $converter;

    public function __construct()
    {
        // CommonMarkCoreExtension を明示登録する Environment 方式。
        // 将来の拡張（脚注・テーブル等）は addExtension() で追加できる。
        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    /** Markdown文字列を受け取りHTML文字列を返す */
    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
