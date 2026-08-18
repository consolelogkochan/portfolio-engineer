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

        // bodyHtml を dangerouslySetInnerHTML で出力することを許容する根拠：
        // - ソースは content/about.md（Gitリポジトリ内・著者管理）であり、
        //   ユーザー入力・外部APIなど未検証データは一切経由しない。
        // - MarkdownRenderer は html_input=allow だが、
        //   MarkdownRenderer クラスのdocコメントで「著者管理コンテンツ専用」と明記済み。
        // - allow_unsafe_links=false により javascript: スキームのリンクは除去される。
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
