<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Services\PageMetaBuilder;
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

            // local / testing 環境ではカスタム ErrorPage を表示せず、
            // Laravel 標準の例外ハンドリングに委ねる。
            // テスト時に例外の詳細（スタックトレース等）を確認できるようにするため。
            //
            // この結果、testing 環境では ErrorPage が render されない（7-2で実測確認済み）。
            // テスト可能にするために testing を除外対象から外すことは、上記の理由により行わない
            // （判定の条件そのものは変えていない）。
            // ErrorPage の meta は固定文言であり、引き続き feature test では検証しない。
            // ただし、エラーページの経路でヘッダー・フッターの値が届かない退行が起きたため（7-6a）、
            // siteName が届くことだけは tests/Feature/SharedPropsTest.php で、
            // テストの中だけ環境を切り替えて確かめている。
            if (app()->environment(['local', 'testing']) || ! in_array($statusCode, [403, 404, 500, 503], true)) {
                return $response;
            }

            // ステータス別の出し分けはしない（config('page_meta.pages.error')の固定文言を使う）。
            // 初回HTML（Blade出力）は固定タイトルだが、JS実行後はReact側のSTATUS_CONTENTにより
            // ステータス別タイトルへ切り替わる（Inertiaがdata-inertia無しのtitleを削除するため。意図した挙動）。
            $pageMetaBuilder = app(PageMetaBuilder::class);
            $meta = $pageMetaBuilder->build('error');

            // titleSuffix：JS実行後、React側（ErrorPage.tsx）がSTATUS_CONTENTのtitleと結合し、
            // サイト名のサフィックス付きのタイトルにするために渡す。
            // 区切り文字・サイト名の定義はconfig('page_meta.site_name')の1箇所のまま
            // （結合処理だけがBlade側とReact側の2箇所に存在する）。
            //
            // siteName：BaseLayout（ヘッダーのロゴ・フッターの©）が使う値。
            // どのルートにも一致しない要求では web ミドルウェアグループが走らず、
            // 共有props（HandleInertiaRequests::share()）が届かないため、ここで直接渡す（7-6a）。
            // 共有propsに BaseLayout が使う項目を足したときは、ここにも足す必要があるかを確認すること。
            return Inertia::render('ErrorPage', [
                'status' => $statusCode,
                'titleSuffix' => $pageMetaBuilder->titleSuffix(),
                'siteName' => $pageMetaBuilder->siteName(),
            ])
                ->withViewData(['pageMeta' => $meta])
                ->toResponse($request)
                ->setStatusCode($statusCode);
        });
    })->create();
