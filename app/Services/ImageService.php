<?php

declare(strict_types=1);

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageService
{
    private readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * 1枚の画像を WebP に変換して保存し、spatie で圧縮する。
     * 「渡された1枚を確実に変換する」だけに徹する。
     * 存在チェック・スキップ判断は持たない（呼び出し元の責務）。
     *
     * @param int $maxWidth  この幅を超える場合のみ縮小。超えなければ縮小しない（拡大もしない）
     * @param int $quality   WebP 品質（0〜100）
     */
    public function optimize(
        string $sourcePath,
        string $destPath,
        int $maxWidth,
        int $quality,
    ): void {
        $image = $this->manager->decode($sourcePath);

        // max_width を超える場合のみ縮小（アスペクト比維持・拡大しない）
        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        // WebP として書き出す（v4.1.x API: encode + WebpEncoder）
        $image->encode(new WebpEncoder(quality: $quality))->save($destPath);

        // spatie でさらに圧縮
        OptimizerChainFactory::create()->optimize($destPath);
    }
}
