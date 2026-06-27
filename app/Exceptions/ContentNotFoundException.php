<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** ファイルが存在しない・読めない場合 */
class ContentNotFoundException extends RuntimeException {}
