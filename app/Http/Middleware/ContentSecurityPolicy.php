<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content-Security-Policy を付ける（7-9e）。
 *
 * 読み込み先を決めるコードと同じ場所に置き、CI のテストで確かめるため、Nginx ではなくアプリに置く。
 * 全体のミドルウェアとして登録する（bootstrap/app.php）。web のグループに入れると、
 * ルートに一致しない 404 の応答を通らないため。HTML 以外の応答にも付くが、害は無い。
 */
class ContentSecurityPolicy
{
    /**
     * 自分のサイトだけを許す。script・img・font・connect などは default-src に任せる。
     * 個別に並べると、足した種類の書き漏らしが起きうるため。
     * default-src に任せられない指令（base-uri・form-action・frame-ancestors）は書く。
     *
     * style-src の 'unsafe-inline' は、Inertia が実行時に差し込む <style>（進捗の表示）のため。
     * 再検討の条件：訪問者が書いたものを表示するようになったとき。
     */
    public const string POLICY = "default-src 'self'; "
        ."style-src 'self' 'unsafe-inline'; "
        ."object-src 'none'; "
        ."base-uri 'self'; "
        ."form-action 'self'; "
        ."frame-ancestors 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Vite の開発サーバーが動いているとき（public/hot がある）は付けない。
        // 開発サーバーのスクリプト・WebSocket と、@viteReactRefresh のインラインのスクリプトが止まるため。
        if (Vite::isRunningHot()) {
            return $response;
        }

        $response->headers->set('Content-Security-Policy', self::POLICY);

        return $response;
    }
}
