<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentNotPublishedException;
use App\Exceptions\ContentParseException;
use App\Services\ContentRepository;
use App\Services\MarkdownRenderer;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function __construct(
        private readonly ContentRepository $repository,
        private readonly MarkdownRenderer $renderer,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Logs/Index', [
            'logs' => $this->repository->listPublishedLogs(),
        ]);
    }

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
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        // bodyHtml を dangerouslySetInnerHTML で出力することを許容する根拠：
        // - ソースは content/logs/*.md（Gitリポジトリ内・著者管理）であり、
        //   ユーザー入力・外部APIなど未検証データは一切経由しない。
        // - MarkdownRenderer は html_input=allow だが著者管理コンテンツ専用と明記済み。
        // - allow_unsafe_links=false により javascript: スキームのリンクは除去される。
        return Inertia::render('Logs/Show', [
            ...$result['frontmatter'],
            'bodyHtml' => $this->renderer->toHtml($result['body']),
        ]);
    }
}
