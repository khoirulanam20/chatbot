<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\WaInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class WaDiagnoseCommand extends Command
{
    protected $signature = 'wa:diagnose
                            {--reset-handoff : Reset percakapan WA yang stuck di handoff/AI off}';

    protected $description = 'Diagnosis kesehatan pipeline WhatsApp (webhook → queue → job → Chatery)';

    public function handle(): int
    {
        $this->info('=== Diagnosis WhatsApp ===');
        $this->newLine();

        $this->checkEnvironment();
        $this->checkRedis();
        $this->checkHorizon();
        $this->checkQueue();
        $this->checkFailedJobs();
        $this->checkConversations();
        $this->checkWaInstances();

        if ($this->option('reset-handoff')) {
            $this->resetHandoff();
        } else {
            $blocked = Conversation::where('channel', 'whatsapp')
                ->where(function ($q) {
                    $q->where('is_ai_active', false)->orWhere('status', 'handoff');
                })
                ->count();

            if ($blocked > 0) {
                $this->newLine();
                $this->warn("Ada {$blocked} percakapan WA blocked. Jalankan: php artisan wa:diagnose --reset-handoff");
            }
        }

        $this->newLine();
        $this->info('Selesai. Cek juga: tail -f storage/logs/laravel.log | grep "WA "');

        return self::SUCCESS;
    }

    private function checkEnvironment(): void
    {
        $this->line('<fg=cyan>Environment</>');
        $this->table(
            ['Key', 'Value'],
            [
                ['APP_ENV', config('app.env')],
                ['QUEUE_CONNECTION', config('queue.default')],
                ['CACHE_STORE', config('cache.default')],
                ['CHATERY_WEBHOOK_SECRET', config('services.chatery.webhook_secret') ? 'set' : 'KOSONG'],
                ['CHATERY_BASE_URL', config('services.chatery.base_url')],
            ]
        );

        if (empty(config('services.chatery.webhook_secret'))) {
            $this->warn('  CHATERY_WEBHOOK_SECRET kosong — webhook diterima tanpa verifikasi signature (disarankan diisi).');
        }

        if (config('queue.default') !== 'redis') {
            $this->warn('QUEUE_CONNECTION bukan redis — pastikan Horizon/worker sesuai.');
        }
    }

    private function checkRedis(): void
    {
        $this->line('<fg=cyan>Redis</>');
        try {
            Redis::connection()->ping();
            $this->info('  Redis: OK');
        } catch (\Throwable $e) {
            $this->error('  Redis: GAGAL — ' . $e->getMessage());
        }
    }

    private function checkHorizon(): void
    {
        $this->line('<fg=cyan>Horizon</>');
        try {
            Artisan::call('horizon:status');
            $output = trim(Artisan::output());
            $this->line('  ' . ($output !== '' ? $output : '(no output)'));
        } catch (\Throwable $e) {
            $this->warn('  horizon:status gagal — ' . $e->getMessage());
            $this->line('  Pastikan Horizon berjalan: php artisan horizon:terminate lalu supervisor restart');
        }
    }

    private function checkQueue(): void
    {
        $this->line('<fg=cyan>Queue whatsapp</>');
        try {
            $size = Queue::size('whatsapp');
            $this->info("  Pending jobs (whatsapp): {$size}");
        } catch (\Throwable $e) {
            $this->warn('  Tidak bisa baca ukuran queue: ' . $e->getMessage());
        }
    }

    private function checkFailedJobs(): void
    {
        $this->line('<fg=cyan>Failed jobs</>');

        if (! Schema::hasTable('failed_jobs')) {
            $this->warn('  Tabel failed_jobs tidak ada.');
            return;
        }

        $total = DB::table('failed_jobs')->count();
        $waFailed = DB::table('failed_jobs')
            ->where('payload', 'like', '%ProcessWhatsAppMessageJob%')
            ->count();

        $this->info("  Total failed: {$total}");
        $this->info("  WA job failed: {$waFailed}");

        if ($waFailed > 0) {
            $recent = DB::table('failed_jobs')
                ->where('payload', 'like', '%ProcessWhatsAppMessageJob%')
                ->orderByDesc('failed_at')
                ->limit(3)
                ->get(['id', 'exception', 'failed_at']);

            foreach ($recent as $job) {
                $firstLine = strtok($job->exception, "\n");
                $this->warn("  [{$job->failed_at}] {$firstLine}");
            }
            $this->line('  Detail: php artisan queue:failed');
        }
    }

    private function checkConversations(): void
    {
        $this->line('<fg=cyan>Percakapan WA</>');

        $total = Conversation::where('channel', 'whatsapp')->count();
        $blocked = Conversation::where('channel', 'whatsapp')
            ->where(function ($q) {
                $q->where('is_ai_active', false)->orWhere('status', 'handoff');
            })
            ->count();

        $this->info("  Total: {$total}");
        $this->info("  Blocked (AI off / handoff): {$blocked}");

        $recent = Conversation::where('channel', 'whatsapp')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get(['id', 'status', 'is_ai_active', 'assigned_agent_id', 'last_message_at']);

        if ($recent->isNotEmpty()) {
            $this->table(
                ['id', 'status', 'ai_active', 'agent', 'last_message'],
                $recent->map(fn ($c) => [
                    $c->id,
                    $c->status,
                    $c->is_ai_active ? 'yes' : 'no',
                    $c->assigned_agent_id ?? '-',
                    $c->last_message_at?->toDateTimeString() ?? '-',
                ])->all()
            );
        }
    }

    private function checkWaInstances(): void
    {
        $this->line('<fg=cyan>WA Instances</>');

        $instances = WaInstance::withoutGlobalScopes()
            ->with('chatbot:id,name')
            ->get(['id', 'instance_id', 'phone_number', 'status', 'typing_enabled', 'chatbot_id']);

        if ($instances->isEmpty()) {
            $this->warn('  Tidak ada WA instance.');
            return;
        }

        $this->table(
            ['id', 'instance_id', 'phone', 'status', 'typing', 'chatbot'],
            $instances->map(fn ($w) => [
                $w->id,
                $w->instance_id ?: '-',
                $w->phone_number ?: '-',
                $w->status,
                $w->typing_enabled ? 'on' : 'off',
                $w->chatbot?->name ?? '-',
            ])->all()
        );

        foreach ($instances as $w) {
            if (empty($w->instance_id)) {
                $this->warn("  Instance #{$w->id}: instance_id kosong — webhook tidak bisa match sessionId.");
            }
        }
    }

    private function resetHandoff(): void
    {
        $this->newLine();
        $this->line('<fg=cyan>Reset handoff WA</>');

        $count = Conversation::where('channel', 'whatsapp')
            ->where(function ($q) {
                $q->where('is_ai_active', false)->orWhere('status', 'handoff');
            })
            ->update([
                'is_ai_active'             => true,
                'status'                   => 'open',
                'assigned_agent_id'        => null,
                'agent_session_started_at' => null,
                'agent_session_ends_at'    => null,
            ]);

        $this->info("  Direset: {$count} percakapan WA.");
    }
}
