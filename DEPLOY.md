# Deploy Production

## Environment wajib

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

SUMOPOD_API_KEY=...
CHATERY_BASE_URL=https://wa.firstudio.id/api
CHATERY_API_KEY=...            # wajib — API key global Chatery untuk connect/QR/outbound
CHATERY_WEBHOOK_SECRET=...   # opsional — jika diisi, webhook wajib header X-Chatery-Signature valid
SUMOPOD_CHAT_MODEL=gpt-4o-mini   # model vision untuk teks & gambar

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
- `2026_06_01_150000` — chatbots.model nullable (model hanya dari Settings → AI)
- `2026_06_02_000001` — typing_enabled di wa_instances
- `2026_06_02_000002` — persona_templates

## Vision (baca gambar)

Agar chatbot bisa menganalisis gambar (widget web + WhatsApp):

- Model di **Settings → AI** (superadmin global atau override tenant) harus mendukung vision, mis. `gpt-4o` atau `gpt-4o-mini` (typo `gpt-40-mini` otomatis dinormalisasi)
- Admin → Edit Chatbot → centang **Izinkan upload gambar di widget**
- `APP_URL` harus URL publik HTTPS yang benar (Sumopod/OpenAI fetch gambar dari `/storage/...`)
- Pastikan `php artisan storage:link` sudah dijalankan

## Humanisasi persona

Chatbot lama **tidak** otomatis humanisasi sampai admin buka **Persona** dan simpan (dengan atau tanpa mengaktifkan humanisasi).

Setelah deploy, buka Persona tiap chatbot aktif dan sesuaikan pengaturan humanisasi.

## Smoke test pasca-deploy

1. `php artisan horizon:terminate` lalu pastikan Horizon aktif kembali
2. `php artisan wa:diagnose` → Redis OK, Horizon running, pending jobs 0 atau diproses
3. Login superadmin → **Settings → AI** → model chat tersimpan (mis. `gpt-4o-mini`) → cek `.env` ada `SUMOPOD_CHAT_MODEL=...`
4. Login admin → Edit Chatbot → centang **Izinkan upload gambar di widget**
5. Login admin → Persona chatbot → simpan humanisasi
6. Widget chat → multi-bubble jika humanize ON + channel web
7. Widget chat → upload gambar → AI membalas analisis (bukan hanya menampilkan file)
8. WA masuk → typing (jika enabled) + balasan teks; pesan muncul di Admin → Percakapan
9. WA kirim gambar → AI membalas analisis gambar
10. Webhook tanpa signature → 401 (jika secret diset)
11. `php artisan conversations:expire-agent-sessions` → exit 0

### Checklist verifikasi production

```bash
# Migrasi terbaru sudah jalan
php artisan migrate:status | grep 2026_06_01_150000

# Model AI terkonfigurasi (tidak kosong)
php artisan tinker --execute="echo config('services.sumopod.chat_model');"

# Storage publik untuk gambar
ls -la public/storage

# APP_URL benar (HTTPS, domain production)
php artisan tinker --execute="echo config('app.url');"
```

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
