<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class DebugWaTrace
{
    private const SESSION = 'd87705';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function log(
        string $hypothesisId,
        string $location,
        string $message,
        array $data = [],
        string $runId = 'pre-fix'
    ): void {
        $payload = [
            'sessionId'    => self::SESSION,
            'runId'        => $runId,
            'hypothesisId' => $hypothesisId,
            'location'     => $location,
            'message'      => $message,
            'data'         => $data,
            'timestamp'    => (int) (microtime(true) * 1000),
        ];

        // #region agent log
        Log::info('[DBG-d87705] ' . $message, [
            'hypothesisId' => $hypothesisId,
            'location'     => $location,
            'runId'        => $runId,
            ...$data,
        ]);
        // #endregion

        foreach ([
            base_path('.cursor/debug-d87705.log'),
            storage_path('logs/debug-d87705.log'),
        ] as $path) {
            $dir = dirname($path);
            if (is_dir($dir) || @mkdir($dir, 0755, true)) {
                @file_put_contents(
                    $path,
                    json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n",
                    FILE_APPEND | LOCK_EX
                );
            }
        }
    }
}
