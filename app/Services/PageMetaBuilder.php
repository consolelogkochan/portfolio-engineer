<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

/**
 * ページのtitle/description/OGP/Twitter Card用の値を組み立てる。
 *
 * 責務：ページ識別子（config('page_meta.pages')のキー）と動的な上書き値から、
 * resources/views/app.blade.php へ渡す配列（title/description/ogImage/ogType/ogUrl）を返す。
 *
 * 責務外：Markdownの読み込み（ContentRepositoryの責務）、HTMLの出力（Bladeの責務）。
 */
class PageMetaBuilder
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  string  $page  config('page_meta.pages')のキー（ルート名ベース。例: 'home', 'works.index'）
     * @param  array{title?: string|null, description?: string, ogImage?: string|null, ogType?: string}  $overrides
     *                                                                                                               動的なページ（作品詳細・ログ詳細・About等）がfrontmatter由来の値で
     *                                                                                                               config上の固定文言を上書きするために使う。
     * @return array{title: string, description: string, ogImage: string, ogType: string, ogUrl: string}
     */
    public function build(string $page, array $overrides = []): array
    {
        // config('page_meta.pages')に該当キーが無くても例外は投げない（静かにデフォルト値へフォールバック）。
        // キーの綴りミス等で意図せずmetaが空になりうる点はconfig/page_meta.php側にコメントで明記済み。
        //
        // 注意：config("page_meta.pages.{$page}")のようにドット記法で直接引くと、$pageが
        // 'works.index'のようにドットを含む文字列の場合、Laravelがpages→works→indexと
        // 階層探索してしまい単一キーとして一致しない。そのためpages配列をまるごと取得し、
        // 通常の配列アクセスで$pageをリテラルなキーとして扱う。
        $pages = config('page_meta.pages', []);
        $defaults = $pages[$page] ?? [];

        $title = array_key_exists('title', $overrides) ? $overrides['title'] : ($defaults['title'] ?? null);
        $description = $overrides['description'] ?? $defaults['description'] ?? '';
        $ogImage = $overrides['ogImage'] ?? null;
        $ogType = $overrides['ogType'] ?? 'website';

        return [
            'title' => $this->fullTitle($title),
            'description' => $description,
            'ogImage' => $this->absoluteUrl($ogImage ?? (string) config('page_meta.default_og_image')),
            'ogType' => $ogType,
            'ogUrl' => $this->absoluteUrl($this->request->getPathInfo()),
        ];
    }

    /**
     * サイト名サフィックス付きの完成形タイトルを組み立てる。
     * セパレータ（半角スペース＋emダッシュ＋半角スペース）は、旧TS側 lib/siteMeta.ts の
     * pageTitle() 実装をそのまま踏襲したもの。
     */
    private function fullTitle(?string $title): string
    {
        $siteName = (string) config('page_meta.site_name');

        return $title ? "{$title} — {$siteName}" : $siteName;
    }

    /** 相対パスをconfig('app.url')基点の絶対URLにする。既に絶対URL（http(s)://〜）ならそのまま返す */
    private function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim((string) config('app.url'), '/').$path;
    }
}
