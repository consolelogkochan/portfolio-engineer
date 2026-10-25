<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 呼び出し側で既定値を書かずに読む設定のキーが、設定ファイルにあることの検証。
 *
 * 値の正は設定ファイルの1か所（config/spam.php・config/image.php）に置き、呼び出し側
 * （ContactRequest・OptimizeImages）には既定値を書かない（7-8c）。呼び出し側の既定値は、
 * キーを消したことを黙って隠してしまうため。代わりに、キーが消えたらここで気づく。
 */
class ConfigKeysTest extends TestCase
{
    public function test_config_keys_read_without_defaults_exist(): void
    {
        foreach (['spam.min_seconds', 'spam.max_urls', 'image.max_width', 'image.webp_quality'] as $key) {
            $this->assertIsInt(config($key), "config('{$key}') が無いか、整数ではない");
        }
    }
}
