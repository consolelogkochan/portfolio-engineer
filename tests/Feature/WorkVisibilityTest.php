<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ContentParser;
use App\Services\ContentRepository;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

/**
 * 非公開の作品（status が 非公開）を見せないことの検証（7-8b-5）。
 *
 * 非公開の作品は、詳細が404を返し、一覧・トップ・sitemap・同じslugの開発ログからのリンクに出ない。
 * 今のコンテンツに非公開の作品は無い。コンテンツの置き場所は base_path('content') に固定で、
 * テスト用に差し替える仕組みも無いため、ContentParser の戻り値のうち対象の作品の status だけを
 * 非公開に書き換えて確かめる（ファイルは読み書きしない）。
 */
class WorkVisibilityTest extends TestCase
{
    private const string SLUG = 'portfolio-engineer';

    protected function setUp(): void
    {
        parent::setUp();

        // 書き換える前の作品が公開中であること（でなければ、このテストは何も確かめない）
        $this->assertTrue(
            app(ContentRepository::class)->hasPublishedWork(self::SLUG),
            '対象の作品が公開中で存在しない（テストの前提が崩れている）',
        );

        $real = new ContentParser;
        $target = base_path('content/works/'.self::SLUG.'.md');

        $parser = Mockery::mock(ContentParser::class);
        $parser->shouldReceive('parse')->andReturnUsing(function (string $path) use ($real, $target): array {
            $result = $real->parse($path);
            if ($path === $target) {
                $result['frontmatter']['status'] = '非公開';
            }

            return $result;
        });
        $this->instance(ContentParser::class, $parser);
    }

    public function test_unpublished_work_detail_returns_404(): void
    {
        $this->get('/works/'.self::SLUG)->assertNotFound();
    }

    public function test_unpublished_work_is_not_listed_on_works_index_or_home(): void
    {
        $notListed = fn ($works) => ! collect($works)->contains('slug', self::SLUG);

        $this->get('/works')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('works', $notListed)
        );
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('featuredWorks', $notListed)
        );
    }

    public function test_unpublished_work_is_not_in_sitemap(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringNotContainsString('/works/'.self::SLUG.'<', (string) $response->getContent());
    }

    public function test_log_does_not_link_to_unpublished_work(): void
    {
        $this->get('/logs/'.self::SLUG)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('hasRelatedWork', false)
        );
    }
}
