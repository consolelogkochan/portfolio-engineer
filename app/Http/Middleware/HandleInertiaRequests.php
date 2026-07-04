<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // flash はセッションから自動では Inertia props に乗らないため明示的に共有する。
            // errors と異なり、Inertia 親クラスに flash 用の専用ロジックは存在しない。
            'flash' => [
                'success'      => fn () => $request->session()->get('success'),
                'rate_limited' => fn () => $request->session()->get('rate_limited'),
            ],
        ];
    }
}
