<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ContentNotFoundException;
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
        } catch (ContentParseException $e) {
            Log::warning('Work skipped due to parse error', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        // bodyHtml を dangerouslySetInnerHTML で出力することを許容する根拠：
        // - ソースは content/works/*.md（Gitリポジトリ内・著者管理）であり、
        //   ユーザー入力・外部APIなど未検証データは一切経由しない。
        // - MarkdownRenderer は html_input=allow だが、
        //   MarkdownRenderer クラスのdocコメントで「著者管理コンテンツ専用」と明記済み。
        // - allow_unsafe_links=false により javascript: スキームのリンクは除去される。
        // この前提が崩れる場合（UGC等）は html_input=strip に変更すること。
        $meta = $this->pageMeta->build('works.show', [
            'title' => $result['frontmatter']['title'] ?? null,
            'description' => $result['frontmatter']['summary'] ?? '',
            'ogImage' => $result['frontmatter']['thumbnail'] ?? null,
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
