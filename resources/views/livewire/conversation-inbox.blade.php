<div wire:poll.5s>
    <div class="mb-4 flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari kontak..."
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 w-48">
        <select wire:model.live="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            <option value="">Semua Status</option>
            <option value="open">Aktif</option>
            <option value="handoff">Handoff</option>
            <option value="resolved">Selesai</option>
            <option value="spam">Spam</option>
        </select>
        <select wire:model.live="channel" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
            <option value="">Semua Channel</option>
            <option value="web">Web</option>
            <option value="whatsapp">WhatsApp</option>
        </select>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($conversations as $conv)
                <a href="{{ route('admin.conversations.show', $conv) }}"
                   class="flex items-center gap-4 px-5 py-4 hover:bg-gray-50 transition-colors">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($conv->contact?->identifier ?? 'A', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm text-gray-800">
                                {{ $conv->contact?->name ?: $conv->contact?->identifier ?: 'Anonymous' }}
                            </span>
                            @if($conv->channel === 'whatsapp')
                                <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">WA</span>
                            @else
                                <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">Web</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 truncate mt-0.5">{{ $conv->chatbot?->name }}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        @php
                            $sc = ['open' => 'bg-blue-100 text-blue-700', 'resolved' => 'bg-green-100 text-green-700', 'handoff' => 'bg-yellow-100 text-yellow-700', 'spam' => 'bg-red-100 text-red-700'];
                            $sl = ['open' => 'Aktif', 'resolved' => 'Selesai', 'handoff' => 'Handoff', 'spam' => 'Spam'];
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $sc[$conv->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $sl[$conv->status] ?? $conv->status }}
                        </span>
                        <div class="text-xs text-gray-400 mt-1">{{ $conv->last_message_at?->diffForHumans() ?? $conv->created_at->diffForHumans() }}</div>
                    </div>
                </a>
            @empty
                <div class="px-5 py-10 text-center text-gray-500 text-sm">
                    <div class="text-3xl mb-2">💬</div>
                    Tidak ada percakapan
                </div>
            @endforelse
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $conversations->links() }}
        </div>
    </div>
</div>
