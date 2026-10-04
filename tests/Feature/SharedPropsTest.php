<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * ヘッダー・フッター（BaseLayout）が使う値が届く経路の検証（7-6a）。
 *
 * ヘッダーのロゴ・フッターの©はTSXに文字列を持たず、propsのsiteNameを表示する。
 * siteNameは2つの経路で届く：通常のページでは共有props（HandleInertiaRequests::share()）、
 * エラーページでは bootstrap/app.php の respond() が直接渡すprops。この両方の経路で
 * 値が届くことを確かめる。
 * 期待値はconfigから読むため、サイト名の値そのものが正しいかは検査しない。
 */
class SharedPropsTest extends TestCase
{
    public function test_site_name_is_shared_with_pages(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('siteName', config('page_meta.site_name'))
        );
    }

    /**
     * どのルートにも一致しない要求では web ミドルウェアグループが走らず、共有propsが届かない。
     * そのためエラーページは bootstrap/app.php の respond() が siteName を直接渡している。
     * その経路が切れていないことを確かめる。
     *
     * 独自のエラーページは local/testing 環境では使われない（bootstrap/app.php の
     * app()->environment() による判定）。このテストの中だけ $this->app['env'] を production にする。
     * アプリケーションはテストごとに作り直されるため（TestCase::setUp）、他のテストには残らない。
     */
    public function test_site_name_reaches_error_page_for_unmatched_route(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get('/no-such-page');

        $response->assertNotFound();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('siteName', config('page_meta.site_name'))
        );
    }
}
