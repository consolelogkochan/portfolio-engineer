<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;
use Throwable;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize {--force : スキップ判定を無視して全件処理}';
    protected $description = '画像を WebP に変換・最適化する（冪等）';

    private const SCAN_DIRS = [
        'public/images/works',
        'public/images/logs',
        'public/images/about',
    ];

    private const EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function handle(ImageService $service): int
    {
        $maxWidth = (int) config('image.max_width', 1200);
        $quality  = (int) config('image.webp_quality', 80);
        $force    = (bool) $this->option('force');

        $success = 0;
        $failure = 0;
        $skipped = 0;

        foreach (self::SCAN_DIRS as $relDir) {
            $dir = base_path($relDir);
            if (!is_dir($dir)) {
                continue;
            }

            foreach ($this->collectImages($dir) as $sourcePath) {
                $destPath = $this->webpPath($sourcePath);

                // 冪等チェック：.webp が存在し元より新しければスキップ
                if (!$force && $this->isUpToDate($sourcePath, $destPath)) {
                    $this->line("  <fg=gray>SKIP</>  " . $this->relative($sourcePath));
                    $skipped++;
                    continue;
                }

                try {
                    $service->optimize($sourcePath, $destPath, $maxWidth, $quality);
                    $this->line("  <info>OK</info>    " . $this->relative($sourcePath));
                    $success++;
                } catch (Throwable $e) {
                    // 1件壊れても全体を止めない（scanAll と同じ思想）
                    $this->line("  <error>FAIL</error>  " . $this->relative($sourcePath) . " — " . $e->getMessage());
                    $failure++;
                }
            }
        }

        $this->newLine();
        $this->line("成功 <info>{$success}</info> / 失敗 <error>{$failure}</error> / スキップ <fg=gray>{$skipped}</>");

        return $failure > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** 指定ディレクトリ配下の対象画像パスを再帰的に収集 */
    private function collectImages(string $dir): array
    {
        $files    = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()
                && in_array(strtolower($file->getExtension()), self::EXTENSIONS, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** 対応する .webp のパスを返す */
    private function webpPath(string $sourcePath): string
    {
        return preg_replace('/\.(jpe?g|png)$/i', '.webp', $sourcePath);
    }

    /** .webp が存在し、かつ元画像より新しいかどうか */
    private function isUpToDate(string $sourcePath, string $destPath): bool
    {
        return file_exists($destPath) && filemtime($destPath) >= filemtime($sourcePath);
    }

    private function relative(string $path): string
    {
        return str_replace(base_path() . '/', '', $path);
    }
}
