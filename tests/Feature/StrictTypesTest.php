<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * 自前の PHP のファイルに declare(strict_types=1) があることの検証（付け忘れを止める）。
 *
 * Pint の declare_strict_types の規則は、ファイルを選べず、Laravel が用意したファイル
 * （config の雛形・bootstrap/providers.php・database/・public/index.php）にも付けてしまう。
 * それらには付けない（自前の処理が無く、env() の値で止まる危険だけを負うため。7-8c）。
 * そこで、付ける範囲をこのテストの OWN_PATHS の1か所で決め、ここで確かめる。
 */
class StrictTypesTest extends TestCase
{
    /** 自前の範囲。ディレクトリはその下の .php をすべて含む（Blade は除く） */
    private const array OWN_PATHS = [
        'app',
        'routes',
        'bootstrap/app.php',
        'config/image.php',
        'config/page_meta.php',
        'config/spam.php',
        'tests',
    ];

    public function test_own_php_files_declare_strict_types(): void
    {
        $files = [];
        foreach (self::OWN_PATHS as $path) {
            $full = base_path($path);
            if (is_dir($full)) {
                foreach (File::allFiles($full) as $file) {
                    if (str_ends_with($file->getFilename(), '.php') && ! str_ends_with($file->getFilename(), '.blade.php')) {
                        $files[] = $file->getPathname();
                    }
                }
            } else {
                $this->assertFileExists($full, "OWN_PATHS の {$path} が無い");
                $files[] = $full;
            }
        }

        $missing = array_values(array_filter(
            $files,
            fn (string $file) => preg_match('/\A<\?php\s+declare\(strict_types=1\);/', (string) file_get_contents($file)) !== 1,
        ));

        $this->assertNotEmpty($files);
        $this->assertSame([], array_map(fn (string $file) => str_replace(base_path().'/', '', $file), $missing),
            '先頭に declare(strict_types=1); が無いファイル');
    }
}
