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

1. Login admin → Persona chatbot → simpan humanisasi
2. Widget chat → multi-bubble jika humanize ON + channel web
3. WA masuk → multi-bubble jika humanize ON + channel whatsapp
4. Webhook tanpa signature → 401 (jika secret diset)
5. `php artisan conversations:expire-agent-sessions` → exit 0
