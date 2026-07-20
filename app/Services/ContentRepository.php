<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentNotPublishedException;
use App\Exceptions\ContentParseException;
use Illuminate\Support\Facades\Log;

class ContentRepository
{
    public function __construct(private readonly ContentParser $parser) {}

    /**
     * 指定ディレクトリの全.mdを走査してパース済み配列を返す。
     * 壊れた1件はスキップ＆Log::warning（サイトは落とさない）。
     * slug はファイル名（拡張子なし）として付与する。
     *
     * @return array<int, array<string, mixed>>
     */
    public function scanAll(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $items = [];

        foreach (glob("{$dir}/*.md") ?: [] as $path) {
            $slug = basename($path, '.md');
            try {
                $result  = $this->parser->parse($path);
                $items[] = array_merge(['slug' => $slug], $result['frontmatter']);
            } catch (ContentNotFoundException | ContentParseException $e) {
                Log::warning('Content skipped during scan', [
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $items;
    }

    /**
     * 公開中の作品を publishedAt の降順（新しい順）で返す。
     *
     * @return array<int, array<string, mixed>>
     */
    public function listPublishedWorks(): array
    {
        $works = $this->scanAll(base_path('content/works'));

        $published = array_values(
            array_filter($works, fn(array $w) => ($w['status'] ?? '') === '公開中'),
        );

        usort($published, fn(array $a, array $b) =>
            strcmp($b['publishedAt'] ?? '', $a['publishedAt'] ?? ''),
        );

        return $published;
    }

    /**
     * 単一作品を取得する（show アクション用）。
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     * @throws ContentNotFoundException
     * @throws ContentParseException
     */
    public function getWork(string $slug): array
    {
        return $this->parser->parse(base_path("content/works/{$slug}.md"));
    }

    /**
     * 単一ログを取得する（show アクション用）。
     * draft=true のログは「存在するが非公開」として ContentNotPublishedException を投げる。
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     * @throws ContentNotFoundException
     * @throws ContentParseException
     * @throws ContentNotPublishedException
     */
    public function getLog(string $slug): array
    {
        $result = $this->parser->parse(base_path("content/logs/{$slug}.md"));

        if (($result['frontmatter']['draft'] ?? false) === true) {
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
        $path = base_path("content/works/{$slug}.md");
        if (!file_exists($path)) {
            return false;
        }

        try {
            $result = $this->parser->parse($path);
        } catch (ContentNotFoundException | ContentParseException) {
            return false;
        }

        return ($result['frontmatter']['status'] ?? '') === '公開中';
    }

    /**
     * 指定slugのログが公開状態（draft !== true）で存在するかを確認する。
     * 存在確認は正常系（無くて当たり前のケースがある）のため、getLogのように例外は投げずboolで返す。
     * パースエラー時も安全側（false）に倒す。
     */
    public function hasPublishedLog(string $slug): bool
    {
        $path = base_path("content/logs/{$slug}.md");
        if (!file_exists($path)) {
            return false;
        }

        try {
            $result = $this->parser->parse($path);
        } catch (ContentNotFoundException | ContentParseException) {
            return false;
        }

        return ($result['frontmatter']['draft'] ?? false) !== true;
    }
}
