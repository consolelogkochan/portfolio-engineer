<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** コンテンツは存在するが公開条件を満たしていない場合（ログの draft が true、作品の status が 非公開） */
class ContentNotPublishedException extends RuntimeException {}
