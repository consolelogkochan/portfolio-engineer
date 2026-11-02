<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Content-Security-Policy のヘッダの検証（7-9e）。
 *
 * 期待の値は、ミドルウェアの定数を参照せずに文字列で書く。
 * 定数を書き換えたときに、このテストも直す必要が出るようにするため。
 */
class ContentSecurityPolicyTest extends TestCase
{
    private const string EXPECTED = "default-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

    /**
     * @return array<string, array{string, int}>
     */
    public static function pages(): array
    {
        return [
            'top' => ['/', 200],
            'work detail' => ['/works/portfolio-engineer', 200],
            'contact' => ['/contact', 200],
            // ルートに一致しない 404 にも付く（全体のミドルウェアにした理由）
            'not found' => ['/no-such-page', 404],
        ];
    }

    #[DataProvider('pages')]
    public function test_pages_have_the_content_security_policy(string $path, int $status): void
    {
        $response = $this->get($path);

        $response->assertStatus($status);
        $response->assertHeader('Content-Security-Policy', self::EXPECTED);
    }

    public function test_policy_is_not_sent_while_the_vite_dev_server_is_running(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'hot');
        file_put_contents($hotFile, 'http://localhost:5173');
        Vite::useHotFile($hotFile);

        try {
            $response = $this->get('/');

            $response->assertOk();
            $response->assertHeaderMissing('Content-Security-Policy');
        } finally {
            unlink($hotFile);
        }
    }
}
