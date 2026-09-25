<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChunkedUploadService
{
    public const MAX_TOTAL_BYTES = 100 * 1024 * 1024;

    private const STALE_SECONDS = 86400;

    public function appendChunk(string $uploadId, UploadedFile $chunk, int $index, int $total, string $fileName, int $fileSize): ?string
    {
        $this->assertValidId($uploadId);

        if ($fileSize > self::MAX_TOTAL_BYTES) {
            throw ValidationException::withMessages(['chunk' => $this->tooLargeMessage($fileName, $fileSize)]);
        }

        $dir  = $this->uploadDir($uploadId);
        $part = $dir . '/data.part';

        if ($index === 0) {
            $this->pruneStale();
            File::deleteDirectory($dir);
            File::ensureDirectoryExists($dir);
        } elseif (! File::exists($part)) {
            throw ValidationException::withMessages(['chunk' => 'Phiên tải lên không tồn tại hoặc đã hết hạn.']);
        }

        $expectedIndex = (int) (File::exists($dir . '/next') ? File::get($dir . '/next') : 0);
        if ($index !== $expectedIndex) {
            throw ValidationException::withMessages(['chunk' => 'Thứ tự phần tải lên không hợp lệ.']);
        }

        $currentSize = File::exists($part) ? File::size($part) : 0;
        if ($currentSize + $chunk->getSize() > self::MAX_TOTAL_BYTES) {
            File::deleteDirectory($dir);
            throw ValidationException::withMessages(['chunk' => $this->tooLargeMessage($fileName, max($fileSize, $currentSize + $chunk->getSize()))]);
        }

        $in  = fopen($chunk->getRealPath(), 'rb');
        $out = fopen($part, 'ab');
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        File::put($dir . '/next', (string) ($index + 1));

        if ($index + 1 < $total) {
            return null;
        }

        File::put($dir . '/name', basename($fileName));
        File::delete($dir . '/next');

        return $uploadId;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, UploadedFile>
     */
    public function resolve(array $tokens): array
    {
        $files = [];

        foreach ($tokens as $token) {
            if (! is_string($token) || ! $this->isValidId($token)) {
                continue;
            }

            $dir = $this->uploadDir($token);
            if (! File::exists($dir . '/name') || ! File::exists($dir . '/data.part')) {
                continue;
            }

            $files[] = new UploadedFile($dir . '/data.part', File::get($dir . '/name'), null, null, true);
        }

        return $files;
    }

    /**
     * @param  array<int, string>  $tokens
     */
    public function cleanup(array $tokens): void
    {
        foreach ($tokens as $token) {
            if (is_string($token) && $this->isValidId($token)) {
                File::deleteDirectory($this->uploadDir($token));
            }
        }
    }

    private function tooLargeMessage(string $fileName, int $bytes): string
    {
        return sprintf(
            'File "%s" có dung lượng %s MB, vượt quá giới hạn tối đa %d MB. Vui lòng nén hoặc chia nhỏ file trước khi tải lên.',
            basename($fileName),
            number_format($bytes / 1024 / 1024, 1, ',', '.'),
            self::MAX_TOTAL_BYTES / 1024 / 1024,
        );
    }

    private function pruneStale(): void
    {
        $root = $this->userRoot();
        if (! File::isDirectory($root)) {
            return;
        }

        foreach (File::directories($root) as $dir) {
            if (time() - File::lastModified($dir) > self::STALE_SECONDS) {
                File::deleteDirectory($dir);
            }
        }
    }

    private function uploadDir(string $uploadId): string
    {
        return $this->userRoot() . '/' . $uploadId;
    }

    private function userRoot(): string
    {
        return storage_path('app/chunks/' . auth()->id());
    }

    private function isValidId(string $uploadId): bool
    {
        return Str::isUuid($uploadId);
    }

    private function assertValidId(string $uploadId): void
    {
        if (! $this->isValidId($uploadId)) {
            throw ValidationException::withMessages(['upload_id' => 'Mã phiên tải lên không hợp lệ.']);
        }
    }
}
