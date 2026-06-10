# Panduan Knowledge Base untuk Chatbot RAG

Dokumen ini menjelaskan cara menulis Knowledge Base (KB) yang akurat agar chatbot menjawab berdasarkan fakta, bukan halusinasi.

## Cara kerja RAG di sistem ini

1. Dokumen di-upload → teks diekstrak → dipecah menjadi **chunk** (~500 kata, overlap 50 kata)
2. Setiap chunk di-embed dan disimpan di database
3. Saat user bertanya, query di-embed → sistem mencari **5 chunk** paling relevan (cosine similarity)
4. Chunk dengan skor ≥ `rag_min_similarity` (default **0.35**) dikirim ke AI sebagai referensi
5. AI diinstruksikan **hanya** menjawab dari referensi tersebut

**Implikasi:** Tulis konten agar setiap bagian **berdiri sendiri** dan mudah ditemukan oleh pencarian semantik.

## Format yang didukung

| Format | Ekstensi | Catatan |
|--------|----------|---------|
| PDF | `.pdf` | Hindari PDF hasil scan gambar tanpa OCR |
| Word | `.doc`, `.docx` | Disarankan untuk editing |
| Excel | `.xls`, `.xlsx` | Satu baris = satu baris teks |
| CSV | `.csv` | Cocok untuk katalog produk |
| Teks | `.txt` | Paling mudah dikontrol chunk-nya |
| URL | Website | Scrape halaman tunggal atau multi-halaman |

Maksimal **50MB** per file.

## Struktur dokumen yang disarankan

### Metadata (wajib di awal dokumen)

```text
DOKUMEN: [Nama dokumen]
PERUSAHAAN: [Nama brand]
VERSI: [1.0]
TERAKHIR DIPERBARUI: [YYYY-MM-DD]
KONTAK: [email/telepon/WA resmi]
RINGKASAN: [1-2 kalimat isi dokumen]
KATA KUNCI: [sinonim, istilah umum pelanggan]
```

### Pola per section (~300–450 kata)

```text
## [Kategori] — [Subtopik]

PERTANYAAN UMUM: [Pertanyaan yang sering diajukan]
JAWABAN: [Jawaban lengkap 2-5 kalimat, angka & ketentuan eksplisit]
DETAIL: [Langkah, syarat, pengecualian]
CATATAN: [Batasan atau kapan harus hubungi agen]
KATA KUNCI: [variasi cara user bertanya]
```

## 7 prinsip agar retrieval akurat

1. **Ulangi konteks di setiap section** — chunk bisa terpotong; jangan asumsikan bot ingat section sebelumnya.
2. **Sinonim di KATA KUNCI** — "ongkir" dan "biaya pengiriman" harus keduanya ada.
3. **Angka & tanggal eksplisit** — "Rp 150.000", bukan "murah"; "Senin–Jumat 09.00–17.00 WIB", bukan "jam kerja normal".
4. **Satu fakta per kalimat** — hindari paragraf panjang berisi banyak topik.
5. **Pisahkan dokumen per domain** — FAQ, produk, kebijakan/SOP sebagai file terpisah.
6. **Hindari konten bermasalah** — tabel tanpa header, PDF gambar-only, marketing fluff tanpa fakta, info bentrok antar dokumen.
7. **Verifikasi setelah upload** — buka **Preview Chunks** di admin; pastikan tiap chunk masih bermakna.

## Contoh chunk BAIK vs BURUK

### Buruk (sulit di-retrieve)

```text
Kami punya banyak produk dengan harga bervariasi. Untuk info lebih lanjut
silakan hubungi tim kami. Pengiriman cepat ke seluruh Indonesia. Retur bisa
dilakukan sesuai kebijakan. Lihat juga halaman promo kami.
```

**Masalah:** Tidak ada angka, tidak ada pertanyaan eksplisit, banyak topik dalam satu paragraf.

### Baik (mudah di-retrieve)

```text
## Pengiriman — Estimasi Waktu

PERTANYAAN UMUM: Berapa lama pengiriman Toko Elektronik ABC ke Jabodetabek?
JAWABAN: Pengiriman ke Jabodetabek memakan 1-2 hari kerja setelah pembayaran dikonfirmasi.
DETAIL:
- Ongkir Jabodetabek: Rp 15.000
- Gratis ongkir untuk pesanan di atas Rp 500.000
- Kurir: JNE Reguler dan Sicepat
CATATAN: Estimasi tidak termasuk hari libur nasional.
KATA KUNCI: ongkir, biaya kirim, estimasi pengiriman, berapa lama sampai
```

## Template siap pakai

Unduh dari halaman **Admin → Knowledge Base → Unduh Template**, atau salin dari folder `resources/templates/`:

| Template | Cocok untuk |
|----------|-------------|
| `knowledge-base-template.txt` | Panduan layanan umum |
| `knowledge-base-faq.txt` | Pertanyaan umum pelanggan |
| `knowledge-base-produk.txt` | Katalog produk/layanan |
| `knowledge-base-kebijakan.txt` | SOP, kebijakan, prosedur internal |

## Organisasi dokumen

| Jenis | Upload sebagai | Tags disarankan |
|-------|----------------|-----------------|
| FAQ umum | `faq-[brand].txt` | FAQ, Umum |
| Katalog produk | `produk-[kategori].txt` | Produk, Harga |
| Kebijakan | `kebijakan-[nama].txt` | Kebijakan, SOP |
| Kontak & eskalasi | Bagian di template umum | Kontak, CS |

## Alur kerja setelah upload

1. Upload dokumen yang sudah diisi data nyata
2. Tunggu status **indexed** (proses async via queue)
3. Buka detail dokumen → **Preview Chunks**
4. Perbaiki section yang terpotong tidak bermakna, lalu **Reindex**
5. Uji chatbot dengan 10 pertanyaan (lihat bagian Testing)

## Testing (10 pertanyaan wajib)

| # | Jenis | Contoh | Hasil diharapkan |
|---|-------|--------|------------------|
| 1 | Langsung di KB | "Berapa harga Produk A?" | Angka persis dari KB |
| 2 | Sinonim | "Biaya kirim ke Surabaya?" | Sama dengan info ongkir |
| 3 | Di luar KB | "Siapa presiden Indonesia?" | Tolak sopan / out of context |
| 4 | Sapaan | "Halo" | Sapa balik, tidak ditolak |
| 5 | Handoff | "Mau bicara CS" | Eskalasi ke agen |
| 6 | Multi-topik | "Harga + cara bayar?" | Jawab keduanya dari KB |
| 7 | Negasi | "Apakah bisa COD?" | Jawab sesuai kebijakan |
| 8 | Detail | "Langkah retur?" | Langkah terurut dari KB |
| 9 | Versi usang | Tanya info yang sudah diubah | Pastikan KB terbaru sudah di-reindex |
| 10 | Channel | Tes di web & WhatsApp | Tone konsisten dengan persona |

## Penyesuaian `rag_min_similarity`

| Gejala | Solusi |
|--------|--------|
| Bot menolak pertanyaan yang seharusnya bisa dijawab | Turunkan threshold (mis. 0.30) |
| Bot menjawab topik yang tidak relevan | Naikkan threshold (mis. 0.40) |
| Chunk sering terpotong tidak utuh | Perpendek section atau tambah overlap dengan mengulang konteks |

## Checklist akurasi

- [ ] Setiap section bisa dipahami tanpa membaca section lain
- [ ] KATA KUNCI mencakup cara user bertanya sehari-hari
- [ ] Tidak ada informasi bentrok antar dokumen
- [ ] Chunk preview terbaca utuh dan bermakna
- [ ] Tanggal versi dokumen selalu diperbarui saat ada perubahan kebijakan/harga

## Lihat juga

- [Panduan Persona](persona-guide.md) — cara menyusun karakter chatbot yang selaras dengan KB
- Template unduhan di `resources/templates/`
