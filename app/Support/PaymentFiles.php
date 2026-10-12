<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Stores payment QR codes and receipts on the configured disk (Supabase),
 * falling back to local storage so uploads keep working if Supabase is down.
 */
class PaymentFiles
{
    public const FALLBACK_DISK = 'local';

    /**
     * @return array{disk: string, path: string}
     */
    public static function store(UploadedFile $file, string $directory): array
    {
        $disk = config('filesystems.payment_disk', self::FALLBACK_DISK);

        if ($disk !== self::FALLBACK_DISK) {
            try {
                $path = Storage::disk($disk)->putFile($directory, $file);

                if ($path) {
                    return ['disk' => $disk, 'path' => $path];
                }
            } catch (Throwable $e) {
                Log::warning("Payment file upload to [{$disk}] failed; saved locally instead.", ['error' => $e->getMessage()]);
            }
        }

        return [
            'disk' => self::FALLBACK_DISK,
            'path' => Storage::disk(self::FALLBACK_DISK)->putFile($directory, $file),
        ];
    }

    public static function delete(?string $disk, ?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            self::disk($disk)->delete($path);
        } catch (Throwable $e) {
            Log::warning("Could not delete payment file [{$path}] from [{$disk}].", ['error' => $e->getMessage()]);
        }
    }

    public static function response(?string $disk, ?string $path, ?string $downloadName = null, array $headers = []): Response
    {
        try {
            $storage = self::disk($disk);
            abort_unless($path && $storage->exists($path), 404);

            return $downloadName
                ? $storage->download($path, $downloadName, $headers)
                : $storage->response($path, null, $headers);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning("Could not read payment file [{$path}] from [{$disk}].", ['error' => $e->getMessage()]);
            abort(503, 'The image is temporarily unavailable. Please try again in a moment.');
        }
    }

    private static function disk(?string $disk): Filesystem
    {
        return Storage::disk($disk ?: self::FALLBACK_DISK);
    }
}
