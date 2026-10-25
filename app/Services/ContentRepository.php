<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentNotPublishedException;
use App\Exceptions\ContentParseException;
use Illuminate\Support\Facades\Log;

class ContentRepository
{
    /**
     * frontmatterにキーが無い項目の既定値。一覧（scanAll）と詳細（getWork）の両方で、画面に渡す前に埋める。
     * 同じ値が types/work.ts の WorkSchema の .default(...) にもある。TS 側は内容の検証と型のため、
     * こちらは画面に渡す値のため。変えるときは2か所とも変える（7-8b-5）。
     */
    private const array WORK_DEFAULTS = [
        'featured' => false,
        'aiTools' => [],
        'gallery' => [],
    ];

    /**
     * 開発ログの既定値。WORK_DEFAULTS と同じく、types/log.ts の LogSchema の .default(...) と2か所にある。
     * 公開の判定（draft）もこの値を埋めた後に行う。
     */
    private const array LOG_DEFAULTS = [
        'tags' => [],
        'draft' => false,
    ];

    /** 作品が公開されている状態の status の値。TS 側は types/work.ts の WorkSchema の z.enum にある */
    private const string STATUS_PUBLISHED = '公開中';

    public function __construct(private readonly ContentParser $parser) {}

    /**
     * 指定ディレクトリの全.mdを走査してパース済み配列を返す。
     * 壊れた1件はスキップ＆Log::warning（サイトは落とさない）。
     * slug はファイル名（拡張子なし）として付与し、キーが無い項目には $defaults を埋める。
     *
     * @param  array<string, mixed>  $defaults
     * @return array<int, array<string, mixed>>
     */
    public function scanAll(string $dir, array $defaults): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $items = [];

        foreach (glob("{$dir}/*.md") ?: [] as $path) {
            $slug = basename($path, '.md');
            try {
                $result = $this->parser->parse($path);
                $items[] = array_merge(['slug' => $slug], $this->withDefaults($result['frontmatter'], $defaults));
            } catch (ContentNotFoundException|ContentParseException $e) {
                Log::warning('Content skipped during scan', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $items;
    }

    /**
     * frontmatter にキーが無い項目へ既定値を入れる。キーがあれば frontmatter の値を優先する。
     *
     * @param  array<string, mixed>  $frontmatter
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function withDefaults(array $frontmatter, array $defaults): array
    {
        return array_merge($defaults, $frontmatter);
    }

    /**
     * 作品が公開中か（status が 公開中）。
     *
     * @param  array<string, mixed>  $frontmatter
     */
    private function isPublishedWork(array $frontmatter): bool
    {
        return ($frontmatter['status'] ?? '') === self::STATUS_PUBLISHED;
    }

    /**
     * ログが公開か（draft !== true）。LOG_DEFAULTS を埋めた後の frontmatter を受け取る。
     *
     * @param  array<string, mixed>  $frontmatter
     */
    private function isPublicLog(array $frontmatter): bool
    {
        return $frontmatter['draft'] !== true;
    }

    /**
     * publishedAt の降順（新しい順）に並べる。
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function sortByPublishedAtDesc(array $items): array
    {
        usort($items, fn (array $a, array $b) => strcmp($b['publishedAt'] ?? '', $a['publishedAt'] ?? ''));

        return $items;
    }

    /**
     * 公開中の作品を publishedAt の降順（新しい順）で返す。
     *
     * @return array<int, array<string, mixed>>
     */
    public function listPublishedWorks(): array
    {
        $works = $this->scanAll(base_path('content/works'), self::WORK_DEFAULTS);

        return $this->sortByPublishedAtDesc(
            array_values(array_filter($works, $this->isPublishedWork(...))),
        );
    }

    /**
     * 公開中の作品のうち featured=true のものだけを publishedAt 降順で返す（トップページ用）。
     * 「featuredだけを見せる」という絞り込み方針はサーバーが担い、フロントは受け取った配列を
     * そのまま表示することに集中できる（絞り込みロジックの重複・ズレを防ぐ）。
     *
     * @return array<int, array<string, mixed>>
     */
    public function listFeaturedWorks(): array
    {
        return array_values(
            array_filter(
                $this->listPublishedWorks(),
                fn (array $w) => $w['featured'] === true,
            ),
        );
    }

    /**
     * 公開ログ（draft !== true）を publishedAt の降順（新しい順）で返す。
     * 一覧ページ自体は5-12で廃止済みだが、sitemap生成のためにslug収集用として存在する。
     *
     * @return array<int, array<string, mixed>>
     */
    public function listPublishedLogs(): array
    {
        $logs = $this->scanAll(base_path('content/logs'), self::LOG_DEFAULTS);

        return $this->sortByPublishedAtDesc(
            array_values(array_filter($logs, $this->isPublicLog(...))),
        );
    }

    /**
     * About固定ページ（content/about.md）を取得する。
     * slugを取らない単一ファイル固定パスな点が getWork/getLog と異なる。
     * 「1ファイルしか読まないなら Controller から ContentParser を直接使ってもよい」とも考えたが、
     * データアクセスを Repository に集約する既存方針（Controller は ContentParser を直接触らない）
     * との一貫性を優先し、薄いラッパーとしてここに置く。
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     *
     * @throws ContentNotFoundException
     * @throws ContentParseException
     */
    public function getAbout(): array
    {
        return $this->parser->parse(base_path('content/about.md'));
    }

    /**
     * 単一作品を取得する（show アクション用）。
     * 公開中でない作品（status が 非公開）は「存在するが非公開」として ContentNotPublishedException を投げる。
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     *
     * @throws ContentNotFoundException
     * @throws ContentParseException
     * @throws ContentNotPublishedException
     */
    public function getWork(string $slug): array
    {
        $result = $this->parser->parse(base_path("content/works/{$slug}.md"));
        $result['frontmatter'] = $this->withDefaults($result['frontmatter'], self::WORK_DEFAULTS);

        if (! $this->isPublishedWork($result['frontmatter'])) {
            throw new ContentNotPublishedException("Unpublished work is not public: {$slug}");
        }

        return $result;
    }

    /**
     * 単一ログを取得する（show アクション用）。
     * draft=true のログは「存在するが非公開」として ContentNotPublishedException を投げる。
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     *
     * @throws ContentNotFoundException
     * @throws ContentParseException
     * @throws ContentNotPublishedException
     */
    public function getLog(string $slug): array
    {
        $result = $this->parser->parse(base_path("content/logs/{$slug}.md"));
        $result['frontmatter'] = $this->withDefaults($result['frontmatter'], self::LOG_DEFAULTS);

        if (! $this->isPublicLog($result['frontmatter'])) {
            throw new ContentNotPublishedException("Draft log is not public: {$slug}");
        }

        return $result;
    }

    /**
     * 指定slugの作品が「公開中」状態で存在するかを確認する。
     * 存在確認は正常系（無くて当たり前のケースがある）のため、getWorkのように例外は投げずboolで返す。
     * パースエラー時も安全側（false）に倒す。
     */
    public function hasPublishedWork(string $slug): bool
    {
        return $this->existsAndIsPublic(
            base_path("content/works/{$slug}.md"), self::WORK_DEFAULTS, $this->isPublishedWork(...),
        );
    }

    /**
     * 指定slugのログが公開状態（draft !== true）で存在するかを確認する。
     * 存在確認は正常系（無くて当たり前のケースがある）のため、getLogのように例外は投げずboolで返す。
     * パースエラー時も安全側（false）に倒す。
     */
    public function hasPublishedLog(string $slug): bool
    {
        return $this->existsAndIsPublic(
            base_path("content/logs/{$slug}.md"), self::LOG_DEFAULTS, $this->isPublicLog(...),
        );
    }

    /**
     * hasPublishedWork・hasPublishedLog の共通の形。ファイルが無い・パースに失敗したときは false。
     * 公開の判定は、既定値を埋めた後の frontmatter で行う（getWork・getLog と同じ）。
     *
     * @param  array<string, mixed>  $defaults
     * @param  callable(array<string, mixed>): bool  $isPublic
     */
    private function existsAndIsPublic(string $path, array $defaults, callable $isPublic): bool
    {
        if (! file_exists($path)) {
            return false;
        }

        try {
            $result = $this->parser->parse($path);
        } catch (ContentNotFoundException|ContentParseException) {
            return false;
        }

        return $isPublic($this->withDefaults($result['frontmatter'], $defaults));
    }
}
