<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class DebugWaTrace
{
    private const SESSION = 'ae60df';

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
        Log::info('[DBG-ae60df] ' . $message, [
            'hypothesisId' => $hypothesisId,
            'location'     => $location,
            'runId'        => $runId,
            ...$data,
        ]);
        // #endregion

        $path = base_path('.cursor/debug-ae60df.log');
        $dir  = dirname($path);
        if (is_dir($dir) || @mkdir($dir, 0755, true)) {
            @file_put_contents(
                $path,
                json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n",
                FILE_APPEND | LOCK_EX
            );
        }
    }
}
