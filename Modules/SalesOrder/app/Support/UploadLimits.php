<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Http\UploadedFile;

final class UploadLimits
{
    public const IMPORT_MAX_FILE_BYTES = 10 * 1024 * 1024;

    public static function maxFilesPerRequest(): int
    {
        return max(1, (int) ini_get('max_file_uploads'));
    }

    public static function maxPostBytes(): int
    {
        return self::toBytes((string) ini_get('post_max_size'));
    }

    public static function maxFileBytes(): int
    {
        $ini = (int) UploadedFile::getMaxFilesize();

        return $ini > 0 ? min($ini, self::IMPORT_MAX_FILE_BYTES) : self::IMPORT_MAX_FILE_BYTES;
    }

    public static function toArray(): array
    {
        return [
            'maxFiles'     => self::maxFilesPerRequest(),
            'maxPostBytes' => self::maxPostBytes(),
            'maxFileBytes' => self::maxFileBytes(),
        ];
    }

    private static function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
