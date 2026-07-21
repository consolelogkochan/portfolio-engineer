<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentRepository;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly ContentRepository $repository) {}

    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'featuredWorks' => $this->repository->listFeaturedWorks(),
        ]);
    }
}
