<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ContentRepository;
use Tests\TestCase;

/**
 * サーバーサイドで出力するmeta情報（title/description/OGP/Twitter Card）の検証（7-2）。
 *
 * 固定文言（Home/Works一覧/Contact/Aboutのtitle）はconfig('page_meta')から取得した値と比較する。
 * 動的ページ（作品詳細・ログ詳細・Aboutのdescription）は、7-6で実コンテンツに差し替わるため
 * 文言そのものは検証せず、meta要素の存在・空でないこと・絶対URL形式であること等の
 * 構造のみを検証する。
 *
 * ErrorPage（404等）はbootstrap/app.phpのrespondでlocal/testing環境が除外されるため、
 * テスト環境ではカスタムErrorPageを描画できない。bootstrap/app.phpの環境判定を
 * 迂回するテストは意図的に書かない（このファイルにErrorPageのテストが無いのはそのため）。
 */
class PageMetaTest extends TestCase
{
    /** <title>〜</title>の中身を取り出す */
    private function extractTitle(string $html): ?string
    {
        if (! preg_match('/<title>(.*?)<\/title>/s', $html, $m)) {
            return null;
        }

        return $m[1];
    }

    /** <meta {$attr}="{$value}" content="..."> のcontentを取り出す */
    private function extractMeta(string $html, string $attr, string $value): ?string
    {
        $pattern = '/<meta\s+'.$attr.'="'.preg_quote($value, '/').'"\s+content="([^"]*)"/';
        if (! preg_match($pattern, $html, $m)) {
            return null;
        }

        return $m[1];
    }

    /**
     * 全ページ共通の構造的な検証（存在・空でないこと・title=og:title一致・絶対URL形式・og:type）。
     * 文言の中身までは見ない（文言の検証は呼び出し側で個別に行う）。
     */
    private function assertMetaStructure(string $html, string $expectedOgType): void
    {
        $title = $this->extractTitle($html);
        $this->assertNotEmpty($title, '<title>が存在しないか空');

        $description = $this->extractMeta($html, 'name', 'description');
        $this->assertNotEmpty($description, 'meta[name=description]が存在しないか空');

        $ogTitle = $this->extractMeta($html, 'property', 'og:title');
        $ogDescription = $this->extractMeta($html, 'property', 'og:description');
        $ogType = $this->extractMeta($html, 'property', 'og:type');
        $ogUrl = $this->extractMeta($html, 'property', 'og:url');
        $ogImage = $this->extractMeta($html, 'property', 'og:image');

        $this->assertNotEmpty($ogTitle, 'og:titleが存在しないか空');
        $this->assertNotEmpty($ogDescription, 'og:descriptionが存在しないか空');
        $this->assertNotEmpty($ogType, 'og:typeが存在しないか空');
        $this->assertNotEmpty($ogUrl, 'og:urlが存在しないか空');
        $this->assertNotEmpty($ogImage, 'og:imageが存在しないか空');

        $twitterCard = $this->extractMeta($html, 'name', 'twitter:card');
        $twitterTitle = $this->extractMeta($html, 'name', 'twitter:title');
        $twitterDescription = $this->extractMeta($html, 'name', 'twitter:description');
        $twitterImage = $this->extractMeta($html, 'name', 'twitter:image');

        $this->assertNotEmpty($twitterCard, 'twitter:cardが存在しないか空');
        $this->assertNotEmpty($twitterTitle, 'twitter:titleが存在しないか空');
        $this->assertNotEmpty($twitterDescription, 'twitter:descriptionが存在しないか空');
        $this->assertNotEmpty($twitterImage, 'twitter:imageが存在しないか空');

        $this->assertSame($title, $ogTitle, '<title>とog:titleが一致しない');

        $appUrl = rtrim((string) config('app.url'), '/');
        $this->assertStringStartsWith($appUrl, (string) $ogUrl, 'og:urlがconfig(app.url)基点の絶対URLになっていない');
        $this->assertMatchesRegularExpression('#^https?://#', (string) $ogImage, 'og:imageが絶対URLになっていない');

        $this->assertSame($expectedOgType, $ogType);
    }

    /** config('page_meta.site_name')を使い、PageMetaBuilderと同じ規則で完成形タイトルを組み立てる */
    private function expectedFullTitle(?string $title): string
    {
        $siteName = (string) config('page_meta.site_name');

        return $title ? "{$title} — {$siteName}" : $siteName;
    }

    public function test_home_page_has_required_meta_tags(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertMetaStructure($html, 'website');

        // Home/Works一覧/Contactは固定文言なので、config('page_meta')の値と比較する
        $pages = config('page_meta.pages');
        $this->assertSame($this->expectedFullTitle($pages['home']['title']), $this->extractTitle($html));
        $this->assertSame($pages['home']['description'], $this->extractMeta($html, 'name', 'description'));
    }

    public function test_works_index_page_has_required_meta_tags(): void
    {
        $response = $this->get('/works');
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertMetaStructure($html, 'website');

        $pages = config('page_meta.pages');
        $this->assertSame($this->expectedFullTitle($pages['works.index']['title']), $this->extractTitle($html));
        $this->assertSame($pages['works.index']['description'], $this->extractMeta($html, 'name', 'description'));
    }

    public function test_works_show_page_has_required_meta_tags(): void
    {
        // 作品詳細はtitle/description/ogImageがすべてfrontmatter由来の動的値（7-6で実コンテンツに
        // 差し替わる）。文言そのものは検証せず、構造のみを検証する。
        $response = $this->get('/works/portfolio-engineer');
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertMetaStructure($html, 'article');
    }

    public function test_logs_show_page_has_required_meta_tags(): void
    {
        // ログ詳細も同様に、title/description/ogImageが動的値。構造のみを検証する。
        $response = $this->get('/logs/portfolio-engineer');
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertMetaStructure($html, 'article');
    }

    public function test_about_page_has_required_meta_tags(): void
    {
        $response = $this->get('/about');
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertMetaStructure($html, 'website');

        // Aboutのtitleは固定（config）だが、descriptionはcontent/about.mdのtitle（肩書き）フィールド
        // 由来の動的値のため、titleのみconfigの値と比較し、descriptionは文言を検証しない。
        $pages = config('page_meta.pages');
        $this->assertSame($this->expectedFullTitle($pages['about']['title']), $this->extractTitle($html));
    }

    public function test_contact_page_has_required_meta_tags(): void
    {
        $response = $this->get('/contact');
        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertMetaStructure($html, 'website');

        $pages = config('page_meta.pages');
        $this->assertSame($this->expectedFullTitle($pages['contact']['title']), $this->extractTitle($html));
        $this->assertSame($pages['contact']['description'], $this->extractMeta($html, 'name', 'description'));
    }

    /**
     * og:imageの絶対URLからconfig('app.url')を取り除いたパスが、実際にpublic/配下に
     * 存在することを検証する。パス文字列の一致だけでは、ファイルの移動・改名・
     * パイプライン変更で壊れたことを検出できないため（SNSでシェアするまで404に気づけない事故を防ぐ）。
     */
    private function assertOgImageFileExists(string $html, string $context): void
    {
        $ogImage = $this->extractMeta($html, 'property', 'og:image');
        $this->assertNotNull($ogImage, "{$context}: og:imageが見つからない");

        $appUrl = rtrim((string) config('app.url'), '/');
        $this->assertStringStartsWith($appUrl, (string) $ogImage, "{$context}: og:imageが絶対URLになっていない");

        $relativePath = substr((string) $ogImage, strlen($appUrl));
        $filePath = public_path(ltrim($relativePath, '/'));

        $this->assertFileExists($filePath, "{$context}: og:imageの実ファイルが存在しない（{$filePath}）");
    }

    public function test_og_image_file_exists_for_static_pages(): void
    {
        foreach (['/' => 'home', '/works' => 'works.index', '/about' => 'about', '/contact' => 'contact'] as $path => $label) {
            $response = $this->get($path);
            $response->assertOk();
            $this->assertOgImageFileExists((string) $response->getContent(), $label);
        }
    }

    public function test_og_image_file_exists_for_all_published_works(): void
    {
        $works = app(ContentRepository::class)->listPublishedWorks();
        $this->assertNotEmpty($works, '公開中の作品が1件も無い（テストの前提が崩れている）');

        foreach ($works as $work) {
            $response = $this->get("/works/{$work['slug']}");
            $response->assertOk();
            $this->assertOgImageFileExists((string) $response->getContent(), "works/{$work['slug']}");
        }
    }

    public function test_og_image_file_exists_for_all_published_logs(): void
    {
        $logs = app(ContentRepository::class)->listPublishedLogs();
        $this->assertNotEmpty($logs, '公開中のログが1件も無い（テストの前提が崩れている）');

        foreach ($logs as $log) {
            $response = $this->get("/logs/{$log['slug']}");
            $response->assertOk();
            $this->assertOgImageFileExists((string) $response->getContent(), "logs/{$log['slug']}");
        }
    }
}
