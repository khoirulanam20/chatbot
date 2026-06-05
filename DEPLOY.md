# Deploy Production

## Environment wajib

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

SUMOPOD_API_KEY=...
CHATERY_BASE_URL=https://wa.firstudio.id/api
CHATERY_WEBHOOK_SECRET=...   # wajib — webhook ditolak jika kosong di production

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

## Perintah deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan storage:link          # sekali saja
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear        # jika upgrade besar, lalu cache ulang
```

## Proses background

### Queue (Horizon)

```bash
php artisan horizon
```

Queue yang harus aktif: `default`, `documents`, `whatsapp`

**Penting:** Jangan jalankan `php artisan queue:work` manual bersamaan dengan Horizon. Cukup Horizon (via Supervisor).

Setelah setiap deploy, restart Horizon agar worker memuat kode terbaru:

```bash
php artisan horizon:terminate
# Supervisor akan auto-restart Horizon
```

Diagnosis pipeline WhatsApp:

```bash
php artisan wa:diagnose
php artisan wa:diagnose --reset-handoff   # reset percakapan WA stuck handoff/AI off
```

### Scheduler (cron)

```cron
* * * * * cd /path/to/chatbot && php artisan schedule:run >> /dev/null 2>&1
```

Command terjadwal: `conversations:expire-agent-sessions` (setiap menit)

## Migrasi baru (cek sudah dijalankan)

- `2026_06_01_000001` — agent session di conversations
- `2026_06_01_142555` — notifications
- `2026_06_02_000001` — typing_enabled di wa_instances
- `2026_06_02_000002` — persona_templates

## Humanisasi persona

Chatbot lama **tidak** otomatis humanisasi sampai admin buka **Persona** dan simpan (dengan atau tanpa mengaktifkan humanisasi).

Setelah deploy, buka Persona tiap chatbot aktif dan sesuaikan pengaturan humanisasi.

## Smoke test pasca-deploy

1. `php artisan horizon:terminate` lalu pastikan Horizon aktif kembali
2. `php artisan wa:diagnose` → Redis OK, Horizon running, pending jobs 0 atau diproses
3. Login admin → Persona chatbot → simpan humanisasi
4. Widget chat → multi-bubble jika humanize ON + channel web
5. WA masuk → typing (jika enabled) + balasan; pesan muncul di Admin → Percakapan
6. Webhook tanpa signature → 401 (jika secret diset)
7. `php artisan conversations:expire-agent-sessions` → exit 0

### Troubleshooting WA tidak balas (embed jalan)

Embed sinkron di HTTP; WA butuh webhook → queue `whatsapp` → Horizon → Chatery outbound.

```bash
# 1. Cek log setelah kirim pesan WA test
tail -100 storage/logs/laravel.log | grep -E "WA webhook|WA job|WA outbound|WA reply skipped"

# 2. Cek failed jobs
php artisan queue:failed

# 3. Reset handoff jika AI sengaja diam
php artisan wa:diagnose --reset-handoff
```

| Log | Arti |
|-----|------|
| `WA webhook result status=queued` | Webhook OK, job masuk queue |
| `WA webhook result status=no_instance` | `instance_id` WA tidak match `sessionId` Chatery |
| `WA job handoff` / `WA reply skipped` | Percakapan di handoff — reset atau tunggu idle |
| `WA outbound failed` | API key / sessionId / chatId Chatery bermasalah |
| `WA job failed` | Lihat stack di `queue:failed` |
