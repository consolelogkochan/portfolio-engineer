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

class WorkController extends Controller
{
    public function __construct(
        private readonly ContentRepository $repository,
        private readonly MarkdownRenderer $renderer,
        private readonly PageMetaBuilder $pageMeta,
    ) {}

    public function index(): Response
    {
        $meta = $this->pageMeta->build('works.index');

        return Inertia::render('Works/Index', [
            'works' => $this->repository->listPublishedWorks(),
            'pageTitle' => $meta['title'],
        ])->withViewData(['pageMeta' => $meta]);
    }

    public function show(string $slug): Response
    {
        try {
            $result = $this->repository->getWork($slug);
        } catch (ContentNotFoundException) {
            abort(404);
        } catch (ContentNotPublishedException) {
            // ファイルは存在するが status が 非公開。404で返し、ログは残さない
            abort(404);
        } catch (ContentParseException $e) {
            Log::warning('Work skipped due to parse error', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        // bodyHtml（content/works/*.md）を dangerouslySetInnerHTML で出してよい根拠と、
        // その前提が崩れる条件は MarkdownRenderer のクラスの説明を参照。
        $meta = $this->pageMeta->build('works.show', [
            'title' => $result['frontmatter']['title'] ?? null,
            'description' => $result['frontmatter']['summary'] ?? '',
            // 作品の ogImage（任意）。無ければ null のまま、サイトの既定のOGP画像に委ねる。
            // thumbnail（webp・正方形）は og:image に向かないので使わない（7-7b）
            'ogImage' => $result['frontmatter']['ogImage'] ?? null,
            'ogType' => 'article',
        ]);

        return Inertia::render('Works/Show', [
            ...$result['frontmatter'],
            'slug' => $slug,
            'bodyHtml' => $this->renderer->toHtml($result['body']),
            'hasRelatedLog' => $this->repository->hasPublishedLog($slug),
            'pageTitle' => $meta['title'],
        ])->withViewData(['pageMeta' => $meta]);
    }
}
