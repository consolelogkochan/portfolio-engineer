<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\ContentNotFoundException;
use App\Exceptions\ContentParseException;
use App\Services\ContentRepository;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class WorkController extends Controller
{
    public function __construct(private readonly ContentRepository $repository) {}

    public function index(): Response
    {
        return Inertia::render('Works/Index', [
            'works' => $this->repository->listPublishedWorks(),
        ]);
    }

    public function show(string $slug): Response
    {
        try {
            $result = $this->repository->getWork($slug);
        } catch (ContentNotFoundException) {
            // 存在しないslug = 想定内のアクセス。ログ不要
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
