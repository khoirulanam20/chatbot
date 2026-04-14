@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl">💬</span>
                <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full font-medium">Hari ini</span>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_today']) }}</div>
            <div class="text-sm text-gray-500 mt-0.5">Total Percakapan</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl">✅</span>
                <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full font-medium">Bulan ini</span>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['resolved']) }}</div>
            <div class="text-sm text-gray-500 mt-0.5">Diselesaikan</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl">⭐</span>
                <span class="text-xs text-yellow-600 bg-yellow-50 px-2 py-0.5 rounded-full font-medium">Rating</span>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ $stats['avg_rating'] > 0 ? number_format($stats['avg_rating'], 1) : '-' }}</div>
            <div class="text-sm text-gray-500 mt-0.5">Rating Kepuasan</div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl">🤖</span>
                <span class="text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full font-medium">Aktif</span>
            </div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_chatbots']) }}</div>
            <div class="text-sm text-gray-500 mt-0.5">Total Chatbot</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">Tren Percakapan (7 Hari)</h3>
            <canvas id="trendChart" height="100"></canvas>
        </div>

        {{-- Channel Summary --}}
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-4">Channel Hari Ini</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🌐</span>
                        <span class="text-sm font-medium text-gray-700">Web Widget</span>
                    </div>
                    <span class="font-bold text-blue-700">{{ $stats['web_count'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📱</span>
                        <span class="text-sm font-medium text-gray-700">WhatsApp</span>
                    </div>
                    <span class="font-bold text-green-700">{{ $stats['wa_count'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🔄</span>
                        <span class="text-sm font-medium text-gray-700">Handoff ke Agen</span>
                    </div>
                    <span class="font-bold text-yellow-700">{{ $stats['handoff'] }}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">📂</span>
                        <span class="text-sm font-medium text-gray-700">Dokumen Indexed</span>
                    </div>
                    <span class="font-bold text-gray-700">{{ $stats['total_documents'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Conversations --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Percakapan Terbaru</h3>
            <a href="{{ route('admin.conversations.index') }}" class="text-sm text-indigo-600 hover:text-indigo-700">Lihat semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Kontak</th>
                        <th class="px-6 py-3 text-left">Chatbot</th>
                        <th class="px-6 py-3 text-left">Channel</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Terakhir Pesan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentConversations as $conv)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.conversations.show', $conv) }}" class="text-sm font-medium text-gray-800 hover:text-indigo-600">
                                    {{ $conv->contact?->name ?: $conv->contact?->identifier ?: 'Anonymous' }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $conv->chatbot?->name }}</td>
                            <td class="px-6 py-4">
                                @if($conv->channel === 'whatsapp')
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">📱 WhatsApp</span>
                                @else
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">🌐 Web</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = ['open' => 'bg-blue-100 text-blue-700', 'resolved' => 'bg-green-100 text-green-700', 'handoff' => 'bg-yellow-100 text-yellow-700', 'spam' => 'bg-red-100 text-red-700'];
                                    $statusLabels = ['open' => 'Aktif', 'resolved' => 'Selesai', 'handoff' => 'Handoff', 'spam' => 'Spam'];
                                @endphp
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $statusColors[$conv->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$conv->status] ?? $conv->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $conv->last_message_at?->diffForHumans() ?? $conv->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">Belum ada percakapan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const trendData = @json($trend);
const ctx = document.getElementById('trendChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: trendData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'Percakapan',
            data: trendData.map(d => d.count),
            borderColor: '#4F46E5',
            backgroundColor: 'rgba(79, 70, 229, 0.08)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#4F46E5',
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endsection
