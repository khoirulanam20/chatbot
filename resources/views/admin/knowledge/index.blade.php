@extends('layouts.admin')
@section('title', 'Knowledge Base')

@section('content')
<div class="space-y-6" x-data="{ activeTab: '' }">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Knowledge Base</h2>
            <p class="text-sm text-gray-500 mt-0.5">Tambahkan sumber pengetahuan chatbot dari file atau website</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="activeTab = activeTab === 'upload' ? '' : 'upload'"
                    :class="activeTab === 'upload' ? 'bg-indigo-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                    class="px-4 py-2 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                📤 Upload File
            </button>
            <button @click="activeTab = activeTab === 'url' ? '' : 'url'"
                    :class="activeTab === 'url' ? 'bg-green-700' : 'bg-green-600 hover:bg-green-700'"
                    class="px-4 py-2 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                🌐 Dari Website
            </button>
        </div>
    </div>

    {{-- Upload File Form --}}
    <div x-show="activeTab === 'upload'" x-transition class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Upload Dokumen</h3>
        <form method="POST" action="{{ route('admin.knowledge.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Chatbot *</label>
                    <select name="chatbot_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Pilih chatbot...</option>
                        @foreach($chatbots as $bot)
                            <option value="{{ $bot->id }}" {{ $chatbotId == $bot->id ? 'selected' : '' }}>{{ $bot->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tags (pisahkan dengan koma)</label>
                    <input type="text" name="tags" placeholder="FAQ, Produk, Kebijakan" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
                <input type="text" name="description" placeholder="Deskripsi singkat dokumen..." class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">File Dokumen * <span class="text-gray-400 font-normal">(PDF, DOCX, XLSX, CSV, TXT — max 50MB)</span></label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-indigo-400 transition-colors"
                     x-data="{ files: [] }"
                     @dragover.prevent
                     @drop.prevent="files = Array.from($event.dataTransfer.files); $refs.fileInput.files = $event.dataTransfer.files">
                    <input type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt"
                           x-ref="fileInput" @change="files = Array.from($event.target.files)" class="hidden">
                    <div x-show="files.length === 0">
                        <div class="text-3xl mb-2">📁</div>
                        <p class="text-sm text-gray-600">Drag & drop file di sini atau
                            <button type="button" @click="$refs.fileInput.click()" class="text-indigo-600 font-medium hover:underline">pilih file</button>
                        </p>
                    </div>
                    <div x-show="files.length > 0">
                        <template x-for="(file, i) in files" :key="i">
                            <div class="flex items-center gap-2 text-sm text-gray-700 py-1">
                                <span>📄</span>
                                <span x-text="file.name"></span>
                                <span class="text-gray-400" x-text="'(' + (file.size / 1024 / 1024).toFixed(1) + ' MB)'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                    📤 Upload & Proses
                </button>
                <button type="button" @click="activeTab = ''" class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                    Batal
                </button>
            </div>
        </form>
    </div>

    {{-- URL Scraping Form --}}
    <div x-show="activeTab === 'url'" x-transition class="bg-white rounded-xl shadow-sm border border-green-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-1">Tambah dari Website</h3>
        <p class="text-sm text-gray-500 mb-4">Scrape konten teks dari halaman website sebagai sumber knowledge.</p>

        <form method="POST" action="{{ route('admin.knowledge.store-url') }}" class="space-y-4" x-data="{ crawlMode: 'single' }">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">URL Website *</label>
                    <input type="url" name="url" required placeholder="https://example.com/halaman-faq"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 font-mono">
                    <p class="text-xs text-gray-400 mt-1">Masukkan URL lengkap termasuk https://</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Chatbot *</label>
                    <select name="chatbot_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">Pilih chatbot...</option>
                        @foreach($chatbots as $bot)
                            <option value="{{ $bot->id }}" {{ $chatbotId == $bot->id ? 'selected' : '' }}>{{ $bot->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Sumber</label>
                    <input type="text" name="name" placeholder="Otomatis dari URL jika kosong"
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                </div>
            </div>

            {{-- Mode Scraping --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode Scraping *</label>
                <div class="grid md:grid-cols-2 gap-3">
                    <label class="relative flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-colors"
                           :class="crawlMode === 'single' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="crawl_mode" value="single" x-model="crawlMode" class="mt-0.5 text-green-600">
                        <div>
                            <div class="font-medium text-sm text-gray-800">📄 Halaman Tunggal</div>
                            <div class="text-xs text-gray-500 mt-0.5">Scrape hanya URL yang diinput</div>
                        </div>
                    </label>
                    <label class="relative flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-colors"
                           :class="crawlMode === 'crawl' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="crawl_mode" value="crawl" x-model="crawlMode" class="mt-0.5 text-green-600">
                        <div>
                            <div class="font-medium text-sm text-gray-800">🕷️ Crawl Multi-Halaman</div>
                            <div class="text-xs text-gray-500 mt-0.5">Ikuti link dalam domain yang sama</div>
                        </div>
                    </label>
                </div>
            </div>

            <div x-show="crawlMode === 'crawl'" x-transition class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Maksimal Halaman</label>
                    <select name="max_pages" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                        <option value="10">10 halaman</option>
                        <option value="25">25 halaman</option>
                        <option value="50" selected>50 halaman (default)</option>
                        <option value="100">100 halaman</option>
                        <option value="200">200 halaman</option>
                        <option value="500">500 halaman (semua)</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Jika website punya sitemap.xml, semua halaman akan ditemukan otomatis. Proses lebih lama untuk halaman lebih banyak.</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tags (pisahkan dengan koma)</label>
                    <input type="text" name="tags" placeholder="Website, FAQ, Produk" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
                    <input type="text" name="description" placeholder="Deskripsi singkat sumber..." class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
                ⚠️ <strong>Catatan:</strong> Proses scraping dilakukan secara asynchronous via queue. Pastikan website yang dituju dapat diakses secara publik.
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                    🌐 Scrape & Proses
                </button>
                <button type="button" @click="activeTab = ''" class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50">
                    Batal
                </button>
            </div>
        </form>
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex gap-3 items-center">
        <select name="chatbot_id" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            <option value="">Semua Chatbot</option>
            @foreach($chatbots as $bot)
                <option value="{{ $bot->id }}" {{ $chatbotId == $bot->id ? 'selected' : '' }}>{{ $bot->name }}</option>
            @endforeach
        </select>
    </form>

    {{-- Documents Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Dokumen</th>
                        <th class="px-6 py-3 text-left">Chatbot</th>
                        <th class="px-6 py-3 text-left">Tipe</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Chunks</th>
                        <th class="px-6 py-3 text-left">Upload</th>
                        <th class="px-6 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-sm text-gray-800">{{ $doc->name }}</div>
                                @if($doc->type === 'url')
                                    <a href="{{ $doc->original_name }}" target="_blank" rel="noopener"
                                       class="text-xs text-green-600 hover:underline truncate block max-w-xs">
                                        🔗 {{ $doc->original_name }}
                                    </a>
                                    @if(isset($doc->metadata['crawl_mode']))
                                        <span class="text-xs text-gray-400">
                                            {{ $doc->metadata['crawl_mode'] === 'crawl' ? '🕷️ Crawl ' . ($doc->metadata['max_pages'] ?? '?') . ' hal.' : '📄 Single page' }}
                                        </span>
                                    @endif
                                @else
                                    <div class="text-xs text-gray-400">{{ $doc->original_name }}</div>
                                @endif
                                @if($doc->description)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $doc->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $doc->chatbot?->name }}</td>
                            <td class="px-6 py-4">
                                @if($doc->type === 'url')
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-medium">🌐 URL</span>
                                @else
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full uppercase font-medium">{{ $doc->type }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $colors = ['queued' => 'bg-yellow-100 text-yellow-700', 'processing' => 'bg-blue-100 text-blue-700', 'indexed' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700'];
                                    $icons = ['queued' => '⏳', 'processing' => '⚙️', 'indexed' => '✅', 'failed' => '❌'];
                                    $labels = ['queued' => 'Menunggu', 'processing' => 'Memproses', 'indexed' => 'Terindeks', 'failed' => 'Gagal'];
                                @endphp
                                <span class="px-2 py-1 text-xs rounded-full font-medium {{ $colors[$doc->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $icons[$doc->status] ?? '' }} {{ $labels[$doc->status] ?? $doc->status }}
                                </span>
                                @if($doc->status === 'failed' && $doc->error_message)
                                    <div class="text-xs text-red-500 mt-1 max-w-xs truncate" title="{{ $doc->error_message }}">{{ $doc->error_message }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($doc->chunk_count) }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $doc->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.knowledge.show', $doc) }}" class="text-indigo-600 hover:text-indigo-700 text-xs font-medium">Lihat</a>
                                    <form method="POST" action="{{ route('admin.knowledge.reindex', $doc) }}">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-700 text-xs font-medium" onclick="return confirm('Re-index dokumen ini?')">Re-index</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.knowledge.destroy', $doc) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium" onclick="return confirm('Hapus dokumen ini? Semua chunk akan dihapus.')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500 text-sm">
                            <div class="text-3xl mb-2">📭</div>
                            Belum ada dokumen. Upload dokumen pertama Anda!
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
