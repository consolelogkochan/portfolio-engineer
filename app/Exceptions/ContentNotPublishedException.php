<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** コンテンツは存在するが公開条件を満たしていない場合（draft=true 等） */
class ContentNotPublishedException extends RuntimeException {}
