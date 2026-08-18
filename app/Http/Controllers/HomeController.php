<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentRepository;
use App\Services\PageMetaBuilder;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly ContentRepository $repository,
        private readonly PageMetaBuilder $pageMeta,
    ) {}

    public function __invoke(): Response
    {
        $meta = $this->pageMeta->build('home');

        return Inertia::render('Home', [
            'featuredWorks' => $this->repository->listFeaturedWorks(),
            'pageTitle' => $meta['title'],
        ])->withViewData(['pageMeta' => $meta]);
    }
}
