<?php

namespace App\Console\Commands;

use App\Services\AgentSessionService;
use Illuminate\Console\Command;

class ExpireAgentSessionsCommand extends Command
{
    protected $signature = 'conversations:expire-agent-sessions';

    protected $description = 'Akhiri sesi agen yang sudah lewat dan aktifkan kembali AI';

    public function handle(AgentSessionService $agentSession): int
    {
        $count = $agentSession->expireDueSessions();

        if ($count > 0) {
            $this->info("Berhasil mengakhiri {$count} sesi agen.");
        }

        return self::SUCCESS;
    }
}
