<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/** ファイルは存在するが frontmatter のパース失敗・連想配列でない・最低限フィールド不足の場合 */
class ContentParseException extends RuntimeException {}
