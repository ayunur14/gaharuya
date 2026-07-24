<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📋 {{ Auth::user()->isSuperAdmin() ? 'Semua Pesanan' : 'Pesanan Saya' }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if ($orders->isEmpty())
                <div class="bg-white rounded-xl border border-gray-200 p-16 text-center text-gray-500">
                    <p class="text-4xl mb-3">📋</p>
                    <p class="text-lg font-medium">Belum ada pesanan</p>
                    <a href="{{ route('dashboard') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Lihat Produk</a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($orders as $order)
                    @php
                        $color = $order->statusColor();
                        $colorMap = [
                            'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-200'],
                            'blue'   => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'border' => 'border-blue-200'],
                            'green'  => ['bg' => 'bg-green-100',  'text' => 'text-green-800',  'border' => 'border-green-200'],
                            'red'    => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'border' => 'border-red-200'],
                            'gray'   => ['bg' => 'bg-gray-100',   'text' => 'text-gray-800',   'border' => 'border-gray-200'],
                        ];
                        $c = $colorMap[$color] ?? $colorMap['gray'];
                    @endphp

                    <div x-data="{ showItems: false, showEdit: false }"
                         class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                        {{-- Header pesanan --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-gray-700">#{{ $order->order_number ?? $order->id }}</span>
                                <span class="text-xs {{ $c['bg'] }} {{ $c['text'] }} {{ $c['border'] }} border rounded-full px-3 py-1 font-semibold">
                                    {{ $order->statusLabel() }}
                                </span>
                                @if ($order->payment_method)
                                    <span class="text-xs bg-gray-100 text-gray-600 border border-gray-200 rounded-full px-2 py-1">
                                        {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}
                                    </span>
                                @endif
                                @if (Auth::user()->isSuperAdmin())
                                    <span class="text-xs text-gray-500">
                                        {{ $order->user->name ?: $order->user->username }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 text-xs text-gray-400">
                                <span class="order-time" data-utc="{{ $order->created_at->toIso8601String() }}"
                                     title="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('d M Y, H:i') }}</span>
                                <button @click="showItems = !showItems"
                                    class="text-indigo-600 hover:text-indigo-800 font-medium"
                                    x-text="showItems ? 'Sembunyikan Detail' : 'Lihat Detail'">
                                </button>
                            </div>
                        </div>

                        {{-- Tabel item (collapsible) --}}
                        <div x-show="showItems"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             style="display:none">
                            <table class="min-w-full divide-y divide-gray-100 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-5 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                                        <th class="px-5 py-2 text-left text-xs font-medium text-gray-500 uppercase">Harga</th>
                                        <th class="px-5 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-5 py-2 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($order->items as $item)
                                    <tr>
                                        <td class="px-5 py-3 font-medium text-gray-800">{{ $item['name'] }}</td>
                                        <td class="px-5 py-3 text-gray-500">Rp {{ number_format($item['price']) }}</td>
                                        <td class="px-5 py-3 text-gray-500">{{ $item['qty'] }}</td>
                                        <td class="px-5 py-3 font-semibold text-indigo-600">Rp {{ number_format($item['subtotal']) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($order->notes)
                                <div class="px-5 py-3 text-sm text-gray-500 bg-gray-50 border-t border-gray-100">
                                    <span class="font-medium text-gray-700">Catatan:</span> {{ $order->notes }}
                                </div>
                            @endif
                        </div>

                        {{-- Footer: total + aksi --}}
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 bg-gray-50 border-t border-gray-100">
                            <span class="font-bold text-indigo-700">Total: Rp {{ number_format($order->total) }}</span>

                            <div class="flex flex-wrap gap-2 items-center">

                                {{-- Tombol Bayar Sekarang (user, status pending_payment) --}}
                                @if (!Auth::user()->isSuperAdmin() && $order->status === 'pending_payment')
                                    @if ($order->snap_token)
                                        <button
                                            onclick="payOrder('{{ $order->snap_token }}')"
                                            class="px-4 py-1.5 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition font-semibold">
                                            💳 Bayar Sekarang
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('orders.generateSnap', $order->id) }}">
                                            @csrf
                                            <button type="submit"
                                                class="px-4 py-1.5 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition font-semibold">
                                                💳 Bayar Sekarang
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                {{-- Tandai lunas (super admin) --}}
                                @if (Auth::user()->isSuperAdmin() && in_array($order->status, ['pending_payment', 'waiting_confirmation']))
                                    <form method="POST" action="{{ route('orders.markPaid', $order->id) }}"
                                          onsubmit="return confirm('Tandai pesanan #{{ $order->id }} sebagai Lunas?')">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs bg-green-600 text-white rounded-md hover:bg-green-700 transition font-semibold">
                                            ✅ Tandai Lunas
                                        </button>
                                    </form>
                                @endif

                                {{-- Hapus (super admin only) --}}
                                @if (Auth::user()->isSuperAdmin())
                                    <form method="POST" action="{{ route('orders.destroy', $order->id) }}"
                                          onsubmit="return confirm('Hapus pesanan #{{ $order->id }}? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs bg-red-500 text-white rounded-md hover:bg-red-600 transition">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                @endif

                                {{-- Batalkan Pesanan --}}
                                @if (!in_array($order->status, ['paid', 'cancelled']))
                                    <form method="POST" action="{{ route('orders.cancel', $order->id) }}"
                                          onsubmit="return confirm('Batalkan pesanan #{{ $order->id }}?')">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs bg-orange-500 text-white rounded-md hover:bg-orange-600 transition font-medium">
                                            ❌ Batalkan Pesanan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                    </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>

    <script>
        document.querySelectorAll('.order-time').forEach(function (el) {
            const utc = el.getAttribute('data-utc');
            if (!utc) return;
            try {
                const date = new Date(utc);
                const formatted = date.toLocaleString('id-ID', {
                    day:    '2-digit',
                    month:  'long',
                    year:   'numeric',
                    hour:   '2-digit',
                    minute: '2-digit',
                    hour12: false,
                });
                el.textContent = formatted;
                el.title = 'Waktu lokal Anda: ' + formatted;
            } catch (e) {}
        });

        function payOrder(snapToken) {
            snap.pay(snapToken, {
                onSuccess: function(result) {
                    window.location.reload();
                },
                onPending: function(result) {
                    window.location.reload();
                },
                onError: function(result) {
                    alert('Pembayaran gagal. Silakan coba lagi.');
                },
                onClose: function() {
                    // user tutup popup tanpa bayar, tidak perlu action
                }
            });
        }
    </script>

    @push('scripts')
    @php
        $snapJs = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    @endphp
    <script src="{{ $snapJs }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endpush
</x-app-layout>
