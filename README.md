# AI Customer Service Chatbot

Platform AI Customer Service Chatbot multi-tenant berbasis Laravel 11 dengan RAG (Retrieval-Augmented Generation), widget embed, dan integrasi WhatsApp via WA Chatery.

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Blade + Alpine.js + Tailwind CSS + Livewire
- **Database**: MySQL 9.0 (native VECTOR type)
- **AI Engine**: Sumopod (OpenAI-compatible API)
- **WhatsApp**: WA Chatery — wa.firstudio.id
- **Queue**: Laravel Queue (Redis di production, database di development)
- **Widget**: Vanilla JavaScript embed snippet

## Persyaratan

- PHP 8.2+
- MySQL 9.0+
- Composer 2.x
- Node.js 18+ (opsional, untuk asset build)
- Redis (production)

## Instalasi

### 1. Clone & Install Dependencies

```bash
cd chatbot
composer install
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_chatbot
DB_USERNAME=root
DB_PASSWORD=your_password

# Sumopod AI
SUMOPOD_API_KEY=your_api_key
SUMOPOD_BASE_URL=https://api.sumopod.ai/v1
SUMOPOD_EMBED_MODEL=text-embedding-3-small
SUMOPOD_CHAT_MODEL=gpt-4o

# WA Chatery
CHATERY_BASE_URL=https://wa.firstudio.id/api
CHATERY_WEBHOOK_SECRET=your_webhook_secret

# Production: Redis
# SESSION_DRIVER=redis
# QUEUE_CONNECTION=redis
# CACHE_STORE=redis
```

### 3. Setup Database

```bash
# Buat database di MySQL
mysql -u root -p -e "CREATE DATABASE ai_chatbot;"

# Jalankan migrasi
php artisan migrate

# Jalankan seeder
php artisan db:seed
```

### 4. Storage Link

```bash
php artisan storage:link
```

### 5. Jalankan Server

```bash
php artisan serve
```

Akses di: http://localhost:8000

### 6. Jalankan Queue Worker

```bash
# Development
php artisan queue:work --queue=documents,whatsapp,default

# Production (dengan Horizon + Redis)
php artisan horizon
```

## Akun Default

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@chatbot.test | password |
| Admin Demo | admin@demo.test | password |
| Operator Demo | operator@demo.test | password |

## Fitur Utama

### Knowledge Base
- Upload PDF, DOCX, Excel, CSV, TXT (max 50MB)
- Proses async via Queue (chunking + embedding)
- Re-index dokumen
- Preview chunk teks

### AI Chat Engine (RAG)
- Embedding query → MySQL VECTOR cosine similarity search
- Top-5 chunks sebagai context
- Chat completion via Sumopod API
- Caching respons di Redis
- Handoff ke agen (trigger keyword)

### Widget Embed
Tambahkan ke website manapun:

```html
<script
  src="https://your-domain.com/chatbot.js"
  data-bot-id="1"
  defer>
</script>
```

### WhatsApp via WA Chatery
1. Buat instance di wa.firstudio.id
2. Set webhook URL: `https://your-domain.com/api/webhook/whatsapp`
3. Input API key di Admin → WhatsApp

### Dashboard Admin
- Inbox percakapan real-time (Livewire, polling 5s)
- Statistik & grafik tren
- Assign percakapan ke agen
- Live handoff AI → Agen
- Export CSV

## Struktur Role

| Role | Akses |
|------|-------|
| Super Admin | Semua fitur + semua tenant |
| Admin | Knowledge base, chatbot, laporan (tenant sendiri) |
| Operator | Lihat & respond percakapan |
| Viewer | Read-only laporan |

## API Endpoints

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/api/chat/message` | POST | Kirim pesan ke chatbot |
| `/api/chat/history/{session}` | GET | Riwayat percakapan |
| `/api/chat/rate` | POST | Rating respons AI |
| `/api/bot/config/{botId}` | GET | Config chatbot (untuk widget) |
| `/api/webhook/whatsapp` | POST | Webhook WA Chatery |
| `/api/analytics/summary` | GET | Statistik (auth required) |

## Pengaturan AI

Masuk ke **Admin → Pengaturan AI** untuk mengkonfigurasi:
- Sumopod API Key
- Base URL
- Model embedding
- Model chat

Setiap chatbot dapat override model chat di halaman edit chatbot.
