<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentParseException;
use App\Services\ContentParser;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class WorkController extends Controller
{
    public function __construct(private readonly ContentParser $parser) {}

    public function show(string $slug): Response
    {
        $path = base_path("content/works/{$slug}.md");

        // 存在しないslugは404（ログ不要：想定内のアクセス）
        if (!file_exists($path)) {
            abort(404);
        }

        try {
            $result = $this->parser->parse($path);
        } catch (ContentNotFoundException $e) {
            abort(404);
        } catch (ContentParseException $e) {
            Log::warning('Work skipped due to parse error', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);
            abort(404);
        }

        return Inertia::render('Works/Show', $result['frontmatter']);
    }
}
