<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * robots.txt（routes/web.php のクロージャ）の検証。
 *
 * Sitemap の行は APP_URL（config('app.url')）から PageMetaBuilder::absoluteUrl() で作る。
 * APP_URL の末尾に / があっても、// にならないことを確かめる。
 */
class RobotsTxtTest extends TestCase
{
    public function test_robots_txt_is_plain_text_with_sitemap_from_app_url(): void
    {
        config(['app.url' => 'https://example.test']);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertSame(
            "User-agent: *\nDisallow:\n\nSitemap: https://example.test/sitemap.xml\n",
            $response->getContent(),
        );
    }

    public function test_trailing_slash_in_app_url_does_not_double_the_slash(): void
    {
        config(['app.url' => 'https://example.test/']);

        $content = (string) $this->get('/robots.txt')->getContent();

        $this->assertStringContainsString("Sitemap: https://example.test/sitemap.xml\n", $content);
        $this->assertStringNotContainsString('//sitemap.xml', $content);
    }
}
