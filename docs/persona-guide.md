# Panduan Membangun Persona Chatbot

Persona menentukan **karakter dan perilaku** chatbot saat menjawab. Di sistem ini, persona digabung otomatis menjadi system prompt untuk engine RAG.

## Field persona

| Field | Deskripsi | Contoh |
|-------|-----------|--------|
| `role` | Peran spesifik bot | "Agen layanan pelanggan Toko Elektronik ABC" |
| `tone` | Gaya bicara | `ramah`, `formal`, `profesional`, `santai` |
| `instructions` | Aturan perilaku (actionable) | "Jawab hanya dari knowledge base..." |
| `restrictions` | Larangan eksplisit | "Jangan mengarang harga..." |
| `greeting_style` | Cara menyapa user | "Sapa hangat, tanyakan kebutuhan" |

Persona **menggantikan** system prompt lama setelah disimpan di halaman Admin → Chatbot → Persona.

## Urutan penyusunan yang disarankan

1. **Isi Knowledge Base dulu** — persona tanpa KB = bot tidak punya fakta untuk dijawab
2. **Tentukan peran** — spesifik ke bisnis & domain
3. **Pilih gaya bicara** — sesuaikan channel (web vs WhatsApp) dan audiens
4. **Tulis instruksi** — fokus pada perilaku yang selaras RAG
5. **Tulis larangan** — cegah halusinasi & respons berbahaya
6. **Atur gaya sapaan** — konsisten dengan tone
7. **Uji 10 pertanyaan nyata** — termasuk di luar KB dan minta handoff

## 1. Peran (`role`)

**Buruk:** "Asisten AI"

**Baik:** "Agen layanan pelanggan Toko Elektronik ABC yang membantu info produk, pesanan, dan garansi"

**Tips:**
- Sebut nama brand/perusahaan
- Sebut domain bantuan (produk, pesanan, teknis, dll)
- Hindari klaim kemampuan di luar KB ("ahli hukum", "dokter")

## 2. Gaya bicara (`tone`)

| Tone | Karakter | Cocok untuk |
|------|----------|-------------|
| `ramah` | Hangat, empatik, approachable | B2C, WhatsApp, retail, edukasi |
| `formal` | Sopan, terstruktur, hormat | B2B, pemerintah, finansial, legal |
| `profesional` | To the point, fokus solusi | Sales, konsultasi, SaaS |
| `santai` | Akrab, conversational | Komunitas, lifestyle, gen-Z |

## 3. Instruksi (`instructions`) — checklist

Tulis instruksi yang **bisa dieksekusi** oleh AI:

- Jawab **hanya** dari knowledge base; jika tidak ada, katakan tidak tahu dan arahkan ke [kontak].
- Jawab dalam **Bahasa Indonesia**; gunakan istilah yang sama dengan dokumen KB.
- Untuk harga, sebut **angka persis** dari KB beserta tanggal/ketentuan jika ada.
- Jawaban **singkat** (1–3 kalimat) kecuali user minta detail atau langkah.
- Jika user minta **diskon khusus / janji di luar KB**, tolak sopan dan tawarkan hubungi sales.
- Untuk masalah teknis, minta **screenshot atau pesan error** sebelum troubleshooting.
- Jika user ketik kata kunci handoff ([daftar keyword]), akui dan jelaskan proses eskalasi.
- Sebut **sumber** jika user meminta konfirmasi kebijakan resmi.

## 4. Larangan (`restrictions`) — wajib eksplisit

Larangan yang efektif mengurangi halusinasi:

- Jangan mengarang harga, promo, stok, atau kebijakan.
- Jangan memberi saran medis, hukum, atau finansial yang tidak ada di KB.
- Jangan membandingkan atau menjelekkan kompetitor.
- Jangan meminta atau menyimpan data sensitif (password, OTP, nomor kartu).
- Jangan mengklaim sebagai manusia jika ditanya identitas bot.
- Jangan membuat janji waktu penyelesaian di luar SLA di KB.

## 5. Gaya sapaan (`greeting_style`)

| Channel | Contoh gaya sapaan |
|---------|-------------------|
| Web widget | "Sapa singkat, perkenalkan sebagai asisten [Brand], tanyakan kebutuhan" |
| WhatsApp | "Sapa hangat, emoji minimal, hindari markdown dan bullet panjang" |
| B2B formal | "Perkenalkan diri + nama perusahaan, tanpa emoji, langsung ke poin" |

## Contoh persona per use case

### Customer Service (e-commerce)

| Field | Isi |
|-------|-----|
| role | Agen layanan pelanggan [Nama Brand] |
| tone | ramah |
| instructions | Jawab singkat dan jelas dalam Bahasa Indonesia berdasarkan knowledge base. Sebut harga, ongkir, dan kebijakan dengan angka persis. Jika informasi tidak ada di KB, akui dengan jujur dan arahkan ke WhatsApp [nomor]. Tanyakan nomor pesanan jika user menanyakan status order. |
| restrictions | Jangan mengarang harga, promo, atau ketersediaan stok. Jangan meminta data kartu kredit atau OTP. Jangan sebut kompetitor. |
| greeting_style | Sapa hangat, tanyakan produk yang dicari atau nomor pesanan |

**Humanisasi disarankan (WhatsApp):** `message_length: short`, `emoji_level: minimal`, `avoid_markdown: true`

---

### Support Teknis (SaaS / Aplikasi)

| Field | Isi |
|-------|-----|
| role | Ahli dukungan teknis [Nama Produk] |
| tone | formal |
| instructions | Berikan langkah troubleshooting terurut dari knowledge base. Minta detail error, versi aplikasi, dan screenshot sebelum memberi solusi. Verifikasi apakah solusi berhasil sebelum menutup topik. Jika masalah tidak ada di KB, eskalasi ke tim teknis via [email/tiket]. |
| restrictions | Jangan menebak penyebab error tanpa data. Jangan instruksikan hapus data, root device, atau tindakan berbahaya. Jangan bagikan kredensial internal tim. |
| greeting_style | Konfirmasi masalah utama pengguna dalam 1 kalimat sebelum memberi langkah |

**Humanisasi disarankan (web):** `message_length: medium`, `emoji_level: none`, `avoid_markdown: false`

---

### Sales Profesional (B2B / Konsultasi)

| Field | Isi |
|-------|-----|
| role | Konsultan penjualan [Nama Brand] |
| tone | profesional |
| instructions | Bantu calon pelanggan memahami manfaat produk/layanan dari knowledge base. Ajukan 1-2 pertanyaan klarifikasi tentang kebutuhan sebelum merekomendasikan. Dorong langkah berikutnya: demo, trial, atau jadwal call dengan tim sales. Sebut harga dan paket persis dari KB. |
| restrictions | Jangan memberi diskon atau janji custom di luar KB. Jangan mengarang fitur yang belum tersedia. Jangan membandingkan dengan kompetitor. |
| greeting_style | Perkenalkan diri singkat sebagai konsultan [Brand], tanyakan kebutuhan utama calon pelanggan |

**Humanisasi disarankan (WhatsApp):** `message_length: short`, `emoji_level: minimal`, `split_bubbles: true`

## Humanisasi (opsional)

Humanisasi mengatur gaya output per channel tanpa mengubah fakta dari KB.

| Setting | Rekomendasi CS (WA) | Rekomendasi Support (web) |
|---------|---------------------|---------------------------|
| `enabled` | true | true |
| `channels` | whatsapp, web | web |
| `message_length` | short | medium |
| `emoji_level` | minimal | none |
| `split_bubbles` | true | false |
| `avoid_markdown` | true | false |
| `use_fillers` | true | false |

**Prinsip:** Humanisasi membuat bot terasa natural, tetapi **tidak boleh** mengubah angka, kebijakan, atau fakta dari KB.

## Selaraskan persona dengan Knowledge Base

| Elemen KB | Elemen Persona yang harus selaras |
|-----------|-----------------------------------|
| Kontak CS di KB | Instruksi eskalasi → arahkan ke kontak yang sama |
| Bahasa istilah di KB | Instruksi → "gunakan istilah di dokumen KB" |
| Kebijakan harga di KB | Larangan → "jangan mengarang harga" |
| Keyword handoff di chatbot settings | Instruksi → sebut proses eskalasi saat keyword terdeteksi |

## Generator AI persona

Di halaman Persona, fitur **Generate dengan AI** membuat draft persona dari deskripsi singkat.

**Tips deskripsi yang baik:**
- Sebut industri: "toko fashion online"
- Sebut audiens: "pelanggan B2C usia 20-35"
- Sebut channel: "utama di WhatsApp"
- Sebut fokus: "info produk, ukuran, dan status pesanan"

**Contoh deskripsi:**
> Chatbot toko fashion ramah di WhatsApp, fokus rekomendasi produk, ukuran, dan tracking pesanan. Jawaban singkat, tidak mengarang promo.

Setelah generate, **selalu review** instruksi & larangan, lalu sesuaikan dengan isi KB Anda.

## Alur uji setelah persona siap

1. Pastikan KB sudah **indexed**
2. Simpan persona di halaman Admin
3. Tes 10 pertanyaan:

| # | Tes | Harapan |
|---|-----|---------|
| 1 | Pertanyaan langsung dari KB | Jawaban akurat dengan angka/fakta |
| 2 | Pertanyaan dengan sinonim | Tetap menemukan info yang benar |
| 3 | Pertanyaan di luar KB | Tolak sopan, tidak mengarang |
| 4 | "Halo" / sapaan | Sapa sesuai `greeting_style` |
| 5 | Minta diskon khusus | Tolak, arahkan ke sales |
| 6 | Minta bicara CS | Eskalasi / handoff |
| 7 | Pertanyaan teknis tanpa detail | Minta info tambahan dulu |
| 8 | "Kamu manusia?" | Jujur sebagai asisten AI |
| 9 | Minta data sensitif | Tolak |
| 10 | Tes di web & WA | Tone & format sesuai channel |

4. Sesuaikan `rag_min_similarity` jika terlalu banyak false reject/accept

## Checklist persona

- [ ] Peran menyebut brand dan domain bantuan
- [ ] Instruksi menyebut "hanya dari knowledge base"
- [ ] Larangan melarang mengarang angka/kebijakan
- [ ] Tone sesuai channel & audiens
- [ ] Kontak eskalasi konsisten dengan isi KB
- [ ] Gaya sapaan selaras dengan tone
- [ ] Sudah diuji dengan pertanyaan nyata pelanggan

## Lihat juga

- [Panduan Knowledge Base](knowledge-base-guide.md)
- Template KB di `resources/templates/`
- Template persona bawaan di Admin → Chatbot → Persona
