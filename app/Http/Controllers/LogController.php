<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentNotPublishedException;
use App\Exceptions\ContentParseException;
use App\Services\ContentRepository;
use App\Services\MarkdownRenderer;
use App\Services\PageMetaBuilder;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function __construct(
        private readonly ContentRepository $repository,
        private readonly MarkdownRenderer $renderer,
        private readonly PageMetaBuilder $pageMeta,
    ) {}

    public function show(string $slug): Response
    {
        try {
            $result = $this->repository->getLog($slug);
        } catch (ContentNotFoundException) {
            abort(404);
        } catch (ContentNotPublishedException) {
            // ファイルは存在するが draft=true。404で返し、ログは残さない
            abort(404);
        } catch (ContentParseException $e) {
            Log::warning('Log skipped due to parse error', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        $hasRelatedWork = $this->repository->hasPublishedWork($slug);

        // OGP画像：対応作品（同slug）があり、その作品が ogImage を持つときはそれを使う。無ければnullのまま、
        // PageMetaBuilder側のデフォルトOGP画像（config('page_meta.default_og_image')）に委ねる。
        // thumbnail（webp・正方形）は og:image に向かないので使わない（7-7b）。
        $ogImage = null;
        if ($hasRelatedWork) {
            try {
                $work = $this->repository->getWork($slug);
                $ogImage = $work['frontmatter']['ogImage'] ?? null;
            } catch (ContentNotFoundException|ContentParseException|ContentNotPublishedException) {
                // hasPublishedWorkの直後でも稀に競合しうる。失敗時はプレースホルダにフォールバック
            }
        }

        $meta = $this->pageMeta->build('logs.show', [
            'title' => $result['frontmatter']['title'] ?? null,
            'description' => $result['frontmatter']['summary'] ?? '',
            'ogImage' => $ogImage,
            'ogType' => 'article',
        ]);

        // bodyHtml（content/logs/*.md）を dangerouslySetInnerHTML で出してよい根拠と、
        // その前提が崩れる条件は MarkdownRenderer のクラスの説明を参照。
        return Inertia::render('Logs/Show', [
            ...$result['frontmatter'],
            'slug' => $slug,
            'bodyHtml' => $this->renderer->toHtml($result['body']),
            'hasRelatedWork' => $hasRelatedWork,
            'pageTitle' => $meta['title'],
        ])->withViewData(['pageMeta' => $meta]);
    }
}
