<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentParser;
use Inertia\Inertia;
use Inertia\Response;

class WorkController extends Controller
{
    public function __construct(private readonly ContentParser $parser) {}

    public function show(string $slug): Response
    {
        $path = base_path("content/works/{$slug}.md");

        $result = $this->parser->parse($path);

        return Inertia::render('Works/Show', $result['frontmatter']);
    }
}
