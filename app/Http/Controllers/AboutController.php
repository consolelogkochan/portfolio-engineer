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

class AboutController extends Controller
{
    public function __construct(
        private readonly ContentRepository $repository,
        private readonly MarkdownRenderer $renderer,
        private readonly PageMetaBuilder $pageMeta,
    ) {}

    public function __invoke(): Response
    {
        try {
            $result = $this->repository->getAbout();
        } catch (ContentNotFoundException) {
            abort(404);
        } catch (ContentParseException $e) {
            Log::warning('About page skipped due to parse error', [
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        // bodyHtml（content/about.md）を dangerouslySetInnerHTML で出してよい根拠と、
        // その前提が崩れる条件は MarkdownRenderer のクラスの説明を参照。
        $meta = $this->pageMeta->build('about', [
            // description=肩書き（titleフィールド）。ogImageは指定せず共通OGP画像に委ねる
            'description' => $result['frontmatter']['title'] ?? '',
        ]);

        return Inertia::render('About', [
            ...$result['frontmatter'],
            'bodyHtml' => $this->renderer->toHtml($result['body']),
            'pageTitle' => $meta['title'],
        ])->withViewData(['pageMeta' => $meta]);
    }
}
