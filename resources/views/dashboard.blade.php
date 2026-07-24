<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🕌 Arabian Oud Collection
            </h2>
            <div class="flex gap-2">
                @if (Auth::check() && Auth::user()->isSuperAdmin())
                    <a href="/produk/tambah" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-xs font-semibold rounded-md hover:bg-green-700 transition">
                        + Tambah Produk
                    </a>
                @endif
                <a href="/cart"
                    x-data="{ count: {{ $cartCount }} }"
                    @cart-updated.window="count = $event.detail.count"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 transition">
                    🛒 Lihat Keranjang
                    <span x-show="count > 0"
                          x-text="count"
                          class="bg-red-500 text-white text-xs font-bold rounded-full min-w-[1.1rem] h-[1.1rem] flex items-center justify-center px-1 leading-none"
                          style="display:none"></span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10" x-data="cartModal({{ Auth::check() ? 'true' : 'false' }})" @keydown.escape.window="close()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($products as $p)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                    @php
                        $cardImages = collect($p->images ?? ['logo_gaharu.jpg'])->map(fn($img) => asset($img))->toArray();
                    @endphp
                    <div
                        x-data="{
                            current: 0,
                            images: @js($cardImages),
                            startX: 0,
                            isDragging: false,
                            dragOffset: 0,
                            onMouseDown(e) { this.isDragging = true; this.startX = e.clientX; this.dragOffset = 0; },
                            onMouseMove(e) { if (!this.isDragging) return; this.dragOffset = e.clientX - this.startX; },
                            onMouseUp(e) {
                                if (!this.isDragging) return;
                                this.isDragging = false;
                                if (this.dragOffset < -50) this.next();
                                else if (this.dragOffset > 50) this.prev();
                                this.dragOffset = 0;
                            },
                            next() { this.current = (this.current + 1) % this.images.length; },
                            prev() { this.current = (this.current - 1 + this.images.length) % this.images.length; }
                        }"
                        @mousedown="onMouseDown"
                        @mousemove="onMouseMove"
                        @mouseup="onMouseUp"
                        @mouseleave="isDragging && onMouseUp($event)"
                        class="relative overflow-hidden h-48 select-none"
                        :class="{ 'cursor-grabbing': isDragging, 'cursor-grab': images.length > 1 }"
                    >
                        <div
                            class="flex h-full"
                            :class="{ 'transition-transform duration-300 ease-in-out': !isDragging }"
                            :style="`transform: translateX(calc(-${current * 100}% + ${dragOffset}px))`"
                        >
                            <template x-for="(img, i) in images" :key="i">
                                <img :src="img" class="w-full h-full object-cover flex-shrink-0" draggable="false" :alt="'{{ $p->name }}'">
                            </template>
                        </div>

                        <!-- Dots indicator -->
                        <template x-if="images.length > 1">
                            <div class="absolute bottom-2 left-0 right-0 flex justify-center gap-1 pointer-events-none">
                                <template x-for="(img, i) in images" :key="i">
                                    <button
                                        @click.stop="current = i"
                                        class="w-2 h-2 rounded-full transition-colors pointer-events-auto"
                                        :class="current === i ? 'bg-white' : 'bg-white/50'"
                                    ></button>
                                </template>
                            </div>
                        </template>

                        <!-- Prev/Next arrows (only when multiple images) -->
                        <template x-if="images.length > 1">
                            <div>
                                <button @click.stop="prev" class="absolute left-1 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white rounded-full w-7 h-7 flex items-center justify-center text-xs transition">&lsaquo;</button>
                                <button @click.stop="next" class="absolute right-1 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white rounded-full w-7 h-7 flex items-center justify-center text-xs transition">&rsaquo;</button>
                            </div>
                        </template>
                    </div>
                    <div class="p-5">
                        @if ($p->sku)
                            <p class="text-xs text-gray-400 font-mono mb-1">{{ $p->sku }}</p>
                        @endif
                        <h3 class="text-lg font-semibold text-gray-800">{{ $p->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $p->description }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <p class="text-indigo-600 font-bold">Rp {{ number_format($p->price) }}</p>
                            @if ($p->stock > 0)
                                <span class="text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-0.5">
                                    Stok: {{ $p->stock }}
                                </span>
                            @else
                                <span class="text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-full px-2 py-0.5">
                                    Stok Habis
                                </span>
                            @endif
                        </div>

                        <div class="flex gap-2 mt-4 flex-wrap">
                            @if ($p->stock > 0)
                            <button type="button"
                                @click="show({ id: {{ $p->id }}, name: @js($p->name), price: {{ (int)$p->price }}, stock: {{ $p->stock }}, desc: @js($p->description), images: @js($cardImages) })"
                                class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                                Beli
                            </button>
                            @else
                            <button type="button" disabled
                                class="px-3 py-1.5 text-xs bg-gray-300 text-gray-500 rounded-md cursor-not-allowed">
                                Stok Habis
                            </button>
                            @endif
                            @if (Auth::check() && Auth::user()->isSuperAdmin())
                                <a href="/edit/{{ $p->id }}" class="px-3 py-1.5 text-xs bg-yellow-400 text-gray-800 rounded-md hover:bg-yellow-500 transition">
                                    Edit
                                </a>
                                <form method="POST" action="/delete/{{ $p->id }}" onsubmit="return confirm('Hapus produk ini?')">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs bg-red-500 text-white rounded-md hover:bg-red-600 transition">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        <!-- ===== MODAL BELI ===== -->
        <div x-show="open"
             style="display:none"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @click.self="close()"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50"></div>

            <!-- Modal Card -->
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden z-10"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <!-- Product image -->
                <div class="relative h-48 bg-gray-100">
                    <img :src="product.images && product.images.length ? product.images[0] : '{{ asset('logo_gaharu.jpg') }}'"
                         class="w-full h-full object-cover" draggable="false">
                    <button @click="close()"
                        class="absolute top-3 right-3 bg-black/40 hover:bg-black/60 text-white rounded-full w-7 h-7 flex items-center justify-center text-base leading-none transition">
                        &times;
                    </button>
                </div>

                <div class="p-5">
                    <!-- Detail produk -->
                    <h3 class="text-lg font-semibold text-gray-800" x-text="product.name"></h3>
                    <p class="text-sm text-gray-500 mt-1 line-clamp-2" x-text="product.desc"></p>
                    <p class="text-indigo-600 font-bold mt-2 text-base"
                       x-text="'Rp ' + product.price.toLocaleString('id-ID')"></p>

                    <!-- Qty stepper -->
                    <div class="mt-4 flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-700">Jumlah:</span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="qty = Math.max(1, qty - 1)"
                                class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-700 hover:bg-gray-100 transition font-bold">−</button>
                            <input type="number" x-model.number="qty" min="1" :max="product.stock"
                                class="w-14 text-center border border-gray-300 rounded-lg py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <button type="button" @click="qty = Math.min(product.stock, qty + 1)"
                                class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-gray-700 hover:bg-gray-100 transition font-bold">+</button>
                        </div>
                        <span class="text-xs text-gray-400">Tersedia: <span x-text="product.stock"></span></span>
                    </div>

                    <!-- Total -->
                    <p class="text-sm text-gray-500 mt-2">
                        Total: <span class="font-semibold text-gray-800"
                                     x-text="'Rp ' + (product.price * qty).toLocaleString('id-ID')"></span>
                    </p>

                    <!-- Tombol aksi -->
                    <div class="flex gap-2 mt-5">
                        <button type="button" @click="close()"
                            class="flex-1 px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Batalkan
                        </button>
                        <button type="button" @click="addToCart()"
                            class="flex-1 px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                            🛒 Beli
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ===== END MODAL ===== -->

    </div>
</x-app-layout>

<script>
function cartModal(isLoggedIn) {
    return {
        open: false,
        product: { id: 0, name: '', price: 0, stock: 1, desc: '', images: [] },
        qty: 1,
        show(data) {
            if (!isLoggedIn) {
                window.location.href = '{{ route('login') }}';
                return;
            }
            this.product = data;
            this.qty = 1;
            this.open = true;
        },
        close() {
            this.open = false;
        },
        async addToCart() {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            try {
                const res = await fetch(`/cart/add/${this.product.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ qty: this.qty }),
                });
                if (res.ok) {
                    const data = await res.json();
                    this.$dispatch('cart-updated', { count: data.count });
                    this.close();
                }
            } catch (e) {
                console.error('Gagal menambahkan ke keranjang:', e);
            }
        },
    };
}
</script>
