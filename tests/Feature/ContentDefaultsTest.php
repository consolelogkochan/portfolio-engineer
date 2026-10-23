<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ContentParser;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

/**
 * front matter にキーが無い項目へ、一覧と詳細の両方で既定値が入ることの検証（7-8b-5）。
 *
 * 既定値は ContentRepository の WORK_DEFAULTS・LOG_DEFAULTS（画面に渡す値）にある。
 * 今のコンテンツは既定値のある項目をすべて書いているため、ContentParser の戻り値から
 * それらのキーを取り除いて確かめる（ファイルは読み書きしない）。
 */
class ContentDefaultsTest extends TestCase
{
    private const string SLUG = 'portfolio-engineer';

    protected function setUp(): void
    {
        parent::setUp();

        $real = new ContentParser;
        $strip = [
            base_path('content/works/'.self::SLUG.'.md') => ['featured', 'aiTools', 'gallery'],
            base_path('content/logs/'.self::SLUG.'.md') => ['tags', 'draft'],
        ];

        $parser = Mockery::mock(ContentParser::class);
        $parser->shouldReceive('parse')->andReturnUsing(function (string $path) use ($real, $strip): array {
            $result = $real->parse($path);
            foreach ($strip[$path] ?? [] as $key) {
                $this->assertArrayHasKey($key, $result['frontmatter'], "{$path} に {$key} が無い（テストの前提が崩れている）");
                unset($result['frontmatter'][$key]);
            }

            return $result;
        });
        $this->instance(ContentParser::class, $parser);
    }

    public function test_works_index_fills_defaults(): void
    {
        $this->get('/works')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('works.0.slug', self::SLUG)
            ->where('works.0.featured', false)
            ->where('works.0.aiTools', [])
            ->where('works.0.gallery', [])
        );
    }

    public function test_home_treats_missing_featured_as_false(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('featuredWorks', [])
        );
    }

    public function test_sitemap_treats_missing_draft_as_false(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('/logs/'.self::SLUG.'<', (string) $response->getContent());
    }

    public function test_work_detail_fills_defaults(): void
    {
        $this->get('/works/'.self::SLUG)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('featured', false)
            ->where('aiTools', [])
            ->where('gallery', [])
            // 同じslugの開発ログの公開の判定（hasPublishedLog）も、draft の既定値で行う
            ->where('hasRelatedLog', true)
        );
    }

    public function test_log_detail_fills_defaults(): void
    {
        $this->get('/logs/'.self::SLUG)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('tags', [])
            ->where('draft', false)
        );
    }
}
