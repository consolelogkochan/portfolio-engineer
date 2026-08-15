<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        // throttle 超過（429）をそのまま返すと Inertia が全画面エラーを表示する。
        // リダイレクト＋flash に変換し、React 側でメッセージを表示する。
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return redirect()->back()->with(
                'rate_limited',
                'しばらく時間をおいてから再度お試しください。',
            );
        });

        // 404/403/500/503 をサイト共通デザインのErrorPage（6-2）に変換する。
        // 型ではなくレスポンスの最終ステータスコードで一括捕捉する（例外の発生経路が
        // 4つとも異なる＝NotFoundHttpException、AuthorizationException、汎用Throwable等）。
        // 上の429処理は既にredirect（302）へ変換済みなので、ここには影響しない。
        // respond はrender系コールバックが全て確定した後の最終レスポンスに対して走るため、
        // 429→redirectの変換結果（302）はここでのステータス判定の対象外のまま素通りする。
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $statusCode = $response->getStatusCode();

            // 開発・テスト環境ではLaravelの詳細デバッグ画面を優先する（カスタムErrorPageは本番相当のみ）。
            if (app()->environment(['local', 'testing']) || ! in_array($statusCode, [403, 404, 500, 503], true)) {
                return $response;
            }

            // 404等はルーティング解決前後、HandleInertiaRequestsミドルウェアを経由しないまま
            // 例外ハンドラに到達することがあるため、BaseLayoutが参照しうる共有データ（app_url）を
            // ここで明示的に補っておく（ミドルウェア外で発生するケースへの対応）。
            Inertia::share('app_url', config('app.url'));

            return Inertia::render('ErrorPage', ['status' => $statusCode])
                ->toResponse($request)
                ->setStatusCode($statusCode);
        });
    })->create();
