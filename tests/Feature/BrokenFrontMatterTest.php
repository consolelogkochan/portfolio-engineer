<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ContentRepository;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * 形の崩れた frontmatter が混ざっても、一覧が落ちないことの検証。
 *
 * ContentRepository はコンテンツの置き場所を base_path('content/...') で決めている。
 * このテストでは、アプリの基点のパスを一時ディレクトリに替え、そこに置いた .md を読ませる
 * （置き場所を差し替える仕組みは無い。WorkVisibilityTest の説明も参照）。
 */
class BrokenFrontMatterTest extends TestCase
{
    private string $root;

    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir().'/broken-front-matter-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists("{$this->root}/content/works");
        File::ensureDirectoryExists("{$this->root}/content/logs");
        $this->publicPath = public_path();
        $this->app->setBasePath($this->root);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    private function putContent(string $relativePath, string $frontMatter): void
    {
        File::put("{$this->root}/content/{$relativePath}", "---\n{$frontMatter}\n---\n\n本文\n");
    }

    /** 文字列だけ・箇条だけの frontmatter は「壊れた1件」として飛ばし、ほかの作品は返す */
    public function test_non_mapping_front_matter_is_skipped_in_lists(): void
    {
        $this->putContent('works/good.md', "title: 正しい作品\nstatus: 公開中\npublishedAt: \"2026-01-01\"");
        $this->putContent('works/scalar.md', 'just a string');
        $this->putContent('works/list.md', "- a\n- b");
        $this->putContent('logs/good.md', "title: 正しいログ\npublishedAt: \"2026-01-01\"");
        $this->putContent('logs/scalar.md', 'just a string');

        $repository = $this->app->make(ContentRepository::class);

        $this->assertSame(['good'], array_column($repository->listPublishedWorks(), 'slug'));
        $this->assertSame(['good'], array_column($repository->listPublishedLogs(), 'slug'));
        $this->assertFalse($repository->hasPublishedWork('scalar'));
        $this->assertFalse($repository->hasPublishedLog('scalar'));
    }

    /** 一覧のページ（/works）も 500 にならず、ほかの作品を出す */
    public function test_works_page_does_not_fail_on_non_mapping_front_matter(): void
    {
        // ページを描くには、ビルドの成果物（public/build/manifest.json）が要る
        symlink($this->publicPath, "{$this->root}/public");
        $this->putContent('works/good.md', "title: 正しい作品\nstatus: 公開中\npublishedAt: \"2026-01-01\"");
        $this->putContent('works/scalar.md', 'just a string');

        $this->get('/works')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('works.0.slug', 'good')->count('works', 1));
    }

    /** 引用符の無い日付も YYYY-MM-DD の文字列になり、引用符つきの日付と混ざっても日付の順に並ぶ */
    public function test_unquoted_and_quoted_dates_sort_by_date(): void
    {
        $this->putContent('works/a.md', "title: A\nstatus: 公開中\npublishedAt: 2026-03-01");
        $this->putContent('works/b.md', "title: B\nstatus: 公開中\npublishedAt: \"2026-02-01\"");
        $this->putContent('works/c.md', "title: C\nstatus: 公開中\npublishedAt: 2025-12-31");
        $this->putContent('works/d.md', "title: D\nstatus: 公開中\npublishedAt: \"2026-04-01\"");
        $this->putContent('logs/a.md', "title: A\npublishedAt: 2026-03-01");
        $this->putContent('logs/b.md', "title: B\npublishedAt: \"2026-02-01\"");

        $repository = $this->app->make(ContentRepository::class);
        $works = $repository->listPublishedWorks();

        $this->assertSame(['d', 'a', 'b', 'c'], array_column($works, 'slug'));
        $this->assertSame(['2026-04-01', '2026-03-01', '2026-02-01', '2025-12-31'], array_column($works, 'publishedAt'));
        $this->assertSame(['a', 'b'], array_column($repository->listPublishedLogs(), 'slug'));
    }
}
