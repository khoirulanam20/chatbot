import { BookOpen, MessageCircle, Smartphone, Users, ShoppingCart, Building2, Stethoscope, HeartPulse } from 'lucide-react';

export const seoCopy = {
    title: 'Chatbot CS untuk Website & WhatsApp — Jawaban dari Dokumen Resmi',
    description: 'Otomatisasi CS 24 jam dari SOP perusahaan Anda. Web widget + WhatsApp, serah ke agen tanpa pindah platform. Demo gratis.',
} as const;

export const heroCopy = {
    eyebrow: 'Layanan pelanggan otomatis · Web & WhatsApp',
    headline: 'Tim CS Anda tidak perlu begadang setiap malam',
    subheadline: 'Chatbot kami jawab pertanyaan pelanggan langsung dari SOP dan dokumen resmi perusahaan — bukan tebak-tebakan AI. Aktif di website dan WhatsApp, serah ke agen kapan saja.',
    ctaPrimary: 'Jadwalkan Demo',
    ctaSecondary: 'Lihat Proses Setup',
    trustRow: [
        { icon: 'Zap', text: 'Dibangun Firstudio' },
        { icon: 'MessageSquare', text: 'WhatsApp via Chatery' },
        { icon: 'Bot', text: 'Jawaban dari dokumen resmi' },
    ],
} as const;

export const valuePillarsCopy = {
    eyebrow: 'Kenapa Kami',
    headline: 'Empat hal yang bikin CS otomatis ini beda',
    pillars: [
        {
            icon: BookOpen,
            title: 'Jawab dari dokumen, bukan asal',
            description: 'Upload SOP, FAQ, dan kebijakan perusahaan. Chatbot hanya jawab dari sana — tidak mengarang informasi di luar topik bisnis Anda.',
        },
        {
            icon: Smartphone,
            title: 'Satu isi, dua channel',
            description: 'Dokumen yang sama melayani chat di website dan WhatsApp bisnis. Update sekali, berlaku di mana-mana.',
        },
        {
            icon: MessageCircle,
            title: 'Terasa seperti chat manusia',
            description: 'Balasan natural dalam Bahasa Indonesia, dengan tempo dan gaya yang bisa disesuaikan per brand.',
        },
        {
            icon: Users,
            title: 'Agen bisa ambil alih kapan saja',
            description: 'Pertanyaan rumit? CS manusia lanjutkan dari dashboard yang sama — tanpa kehilangan riwayat chat.',
        },
    ],
} as const;

export const problemCopy = {
    eyebrow: 'Masalah',
    headline: 'Kenapa pelanggan beralih ke kompetitor?',
    subheadline: 'Bukan karena produk Anda jelek — tapi karena pertanyaan sederhana tidak dijawab cukup cepat.',
    problems: [
        {
            before: 'Jam 10 malam, pelanggan tanya "kapan barang sampai?" — tidak ada yang jawab sampai pagi.',
            after: 'Pertanyaan dijawab otomatis, kapan pun pelanggan chat — bahkan di luar jam kerja.',
        },
        {
            before: 'CS shift pagi jawab "berapa lama pengiriman?" untuk ke-40 kalinya minggu ini.',
            after: 'Pertanyaan rutin ditangani chatbot. CS fokus ke komplain dan kasus yang butuh manusia.',
        },
        {
            before: '47 chat WhatsApp belum dibaca. 3 pelanggan sudah komplain di Instagram.',
            after: 'Setiap pesan masuk langsung direspons. Tidak ada antrean yang menumpuk lagi.',
        },
    ],
} as const;

export const featuresCopy = {
    eyebrow: 'Fitur Utama',
    headline: 'Yang Anda dapat',
    subheadline: 'Semua modul CS dalam satu dashboard — dari upload dokumen sampai pantau percakapan.',
    features: [
        {
            type: 'rag' as const,
            title: 'Dokumen brand',
            description: 'Upload PDF atau tarik konten dari website. Chatbot cari jawaban di dokumen itu — bukan dari internet.',
        },
        {
            type: 'widget' as const,
            title: 'Chat langsung di website',
            description: 'Satu baris kode, widget muncul di pojok website. Warna, sapaan, dan tombol cepat bisa disesuaikan.',
        },
        {
            type: 'whatsapp' as const,
            title: 'Aktif di WhatsApp bisnis',
            description: 'Scan QR dari dashboard, chatbot langsung merespons di WA. Balasan terasa natural, bukan template kaku.',
        },
        {
            type: 'handoff' as const,
            title: 'Serah ke agen CS',
            description: 'Pelanggan minta bicara manusia? Agen ambil alih, AI berhenti sementara, lanjut lagi setelah selesai.',
        },
    ],
} as const;

export const howItWorksCopy = {
    eyebrow: 'Cara Kerja',
    headline: 'Live dalam 3 langkah',
    subheadline: 'Tidak perlu tim IT khusus. Tim operasional Anda bisa setup sendiri.',
    steps: [
        {
            title: 'Siapkan dokumen',
            description: 'Upload SOP, FAQ, kebijakan retur — atau import dari halaman website yang sudah ada.',
        },
        {
            title: 'Atur gaya & channel',
            description: 'Tentukan persona chatbot, pasang widget di website, connect WhatsApp via QR.',
        },
        {
            title: 'Aktifkan & pantau',
            description: 'Chatbot mulai merespons. Anda pantau semua percakapan dari satu dashboard.',
        },
    ],
} as const;

export const channelsCopy = {
    eyebrow: 'Channel Integrasi',
    headline: 'Website dan WhatsApp, satu dashboard',
    subheadline: 'Pelanggan chat dari mana saja — Anda kelola dari satu tempat.',
    features: [
        'Satu knowledge base',
        'Handoff ke agen',
        'Riwayat chat tersimpan',
        'Analytics terpusat',
    ],
} as const;

export const handoffCopy = {
    eyebrow: 'Live Handoff',
    headline: 'Chatbot handle rutin. Agen handle yang rumit.',
    subheadline: 'Tidak perlu pindah aplikasi atau kehilangan konteks percakapan.',
    checklist: [
        'Pelanggan ketik "operator" — agen langsung dapat notifikasi',
        'AI otomatis berhenti saat agen sedang membalas',
        'Selesai? Klik "Aktifkan AI" — chatbot lanjut lagi',
        'Agen lihat riwayat chat lengkap sebelum membalas',
    ],
} as const;

export const useCasesCopy = {
    eyebrow: 'Contoh penerapan',
    headline: 'Sudah cocok untuk operasional seperti ini',
    subheadline: 'Cocok untuk berbagai industri yang butuh CS skala besar dengan akurasi tinggi.',
    cases: [
        {
            icon: ShoppingCart,
            tag: 'E-commerce',
            title: 'Toko Online & Marketplace',
            description: '"Kapan barang sampai?", "Bisa retur?", "Ada stok ukuran L?" — dijawab otomatis dari SOP toko, tanpa CS shift malam.',
        },
        {
            icon: Building2,
            tag: 'Keuangan',
            title: 'Fintech & Perbankan',
            description: 'Chatbot menjelaskan produk KPR, KUR, atau cicilan dari dokumen resmi — tanpa risiko informasi salah karena pakai RAG.',
        },
        {
            icon: Stethoscope,
            tag: 'Kesehatan',
            title: 'Klinik & Rumah Sakit',
            description: 'Jadwal dokter, persiapan lab, dan info BPJS dari knowledge base klinik — pasien dapat jawaban instan di WA.',
        },
        {
            icon: HeartPulse,
            tag: 'B2B',
            title: 'SaaS & Layanan Profesional',
            description: 'Onboarding klien, FAQ teknis, dan update status project — semuanya dari satu knowledge base tim.',
        },
    ],
} as const;

export const faqCopy = {
    eyebrow: 'FAQ',
    headline: 'Pertanyaan yang Sering Diajukan',
    faqs: [
        {
            question: 'Apa bedanya dengan ChatGPT biasa?',
            answer: 'AI CS Chatbot menggunakan RAG — hanya menjawab dari dokumen knowledge base perusahaan Anda. Ada guard out-of-context sehingga chatbot tidak menjawab pertanyaan di luar scope bisnis Anda.',
        },
        {
            question: 'Apakah bisa integrasi WhatsApp?',
            answer: 'Ya. Connect WhatsApp bisnis via scan QR di dashboard. Satu knowledge base melayani widget website dan WhatsApp sekaligus.',
        },
        {
            question: 'Apakah chatbot bisa bahasa Indonesia natural?',
            answer: 'Bisa. Kami atur gaya bahasa supaya terdengar seperti CS asli — tidak kaku seperti bot template, dan bisa disesuaikan dengan tone brand Anda (formal/santai).',
        },
        {
            question: 'Bisakah saya pakai API key AI saya sendiri?',
            answer: 'Tentu. Sistem kami mendukung konfigurasi AI per-tenant, sehingga Anda bisa menggunakan API key OpenAI atau Anthropic milik Anda sendiri.',
        },
        {
            question: 'Berapa lama setup?',
            answer: 'Upload dokumen, atur persona, tempel widget atau scan QR WA — chatbot bisa live dalam beberapa jam, tergantung kelengkapan knowledge base.',
        },
        {
            question: 'Apakah data aman?',
            answer: 'Arsitektur multi-tenant dengan isolasi data per klien. Konfigurasi AI per tenant dan audit log untuk operasional yang transparan.',
        },
        {
            question: 'Apakah bisa handoff ke CS manusia?',
            answer: 'Ya. Agen bisa takeover percakapan kapan saja dari dashboard, pause AI, dan resume AI setelah masalah selesai.',
        },
        {
            question: 'Format dokumen apa saja yang didukung?',
            answer: 'PDF, DOCX, Excel, CSV, TXT (max 50MB), plus import konten dari URL website via web scraping.',
        },
    ],
} as const;

export const contactCopy = {
    eyebrow: 'Hubungi Kami',
    headline: 'Coba dulu, baru putuskan',
    subheadline: 'Demo 30 menit — kami tunjukkan setup dari dokumen Anda sendiri, bukan slide generik.',
    bullets: [
        { icon: 'Zap', title: 'Tanpa komitmen', desc: 'Bebas bertanya tanpa tekanan.' },
        { icon: 'Shield', title: 'Setup demo pakai dokumen Anda', desc: 'Lihat langsung hasilnya.' },
        { icon: 'Clock', title: 'Respons dalam 1 hari kerja', desc: 'Tim kami akan segera menghubungi.' },
    ],
    form: {
        title: 'Form Demo',
        description: 'Ceritakan kebutuhan CS bisnis Anda.',
        submit: 'Kirim Permintaan Demo',
    },
} as const;
