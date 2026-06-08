<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivewireLargeUpload
{
    public function handle(Request $request, Closure $next)
    {
        // Livewire large file uploads may take minutes. Avoid PHP timeouts during the upload request.
        // Note: upload_max_filesize / post_max_size cannot be increased at runtime (PHP_INI_PERDIR).
        @ini_set('max_execution_time', '0');
        @ini_set('max_input_time', '600');
        @set_time_limit(0);

        $start = microtime(true);

        Log::info('Livewire upload request started.', [
            'path' => $request->path(),
            'method' => $request->method(),
            'content_length' => $request->headers->get('content-length'),
            'content_type' => $request->headers->get('content-type'),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            Log::error('Livewire upload request crashed.', [
                'path' => $request->path(),
                'message' => $e->getMessage(),
                'class' => $e::class,
                'user_id' => $request->user()?->id,
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);

            throw $e;
        }

        Log::info('Livewire upload request finished.', [
            'path' => $request->path(),
            'status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
            'user_id' => $request->user()?->id,
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ]);

        return $response;
    }
}
