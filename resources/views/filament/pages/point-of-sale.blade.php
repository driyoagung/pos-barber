<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Point of Sale</h2>
                <p class="text-sm text-gray-500">Kasir cepat untuk transaksi barbershop</p>
            </div>
            <div class="flex gap-2">
                <x-filament::button color="gray" wire:click="clearCart"
                    wire:confirm="Yakin ingin mengosongkan keranjang?">
                    <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4 mr-2 inline-block" />
                    Reset
                </x-filament::button>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Kolom Kiri: Daftar Produk/Layanan -->
            <div class="w-full lg:flex-1 space-y-4">
                <!-- Pencarian -->
                <x-filament::input.wrapper>
                    <x-filament::input type="text" placeholder="Cari produk atau layanan..."
                        wire:model.live.debounce.300ms="search_product" class="w-full" />
                </x-filament::input.wrapper>

                <!-- Loading State -->
                <div wire:loading class="text-center py-4">
                    <x-filament::loading-indicator class="w-6 h-6 mx-auto text-primary-600" />
                    <span class="text-sm text-gray-500">Mencari...</span>
                </div>

                <!-- Grid Produk -->
                <div wire:loading.remove class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @forelse($this->products as $product)
                        <button wire:click="addProduct({{ $product->id }})" wire:loading.attr="disabled"
                            class="relative flex flex-col p-3 text-left transition-all bg-white border rounded-lg shadow-sm hover:shadow-md hover:border-primary-500 dark:bg-gray-800 dark:border-gray-700 disabled:opacity-50">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Stok: {{ $product->stock }} {{ $product->unit }}
                            </div>
                            <div class="mt-2 text-lg font-semibold text-primary-600 dark:text-primary-400">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </div>
                            @if ($product->stock < 1)
                                <div
                                    class="absolute inset-0 bg-gray-100 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 rounded-lg flex items-center justify-center">
                                    <span class="px-2 py-1 text-xs font-medium text-white bg-danger-500 rounded">Stok
                                        Habis</span>
                                </div>
                            @endif
                        </button>
                    @empty
                        <div class="col-span-full text-center py-8 text-gray-500">
                            <x-filament::icon icon="heroicon-m-cube" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                            <p>Tidak ada produk ditemukan</p>
                        </div>
                    @endforelse

                    @foreach ($this->services as $service)
                        <button wire:click="addService({{ $service->id }})" wire:loading.attr="disabled"
                            class="relative flex flex-col p-3 text-left transition-all bg-white border rounded-lg shadow-sm hover:shadow-md hover:border-primary-500 dark:bg-gray-800 dark:border-gray-700 disabled:opacity-50">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $service->name }}</div>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Durasi: {{ $service->duration ?? '-' }} menit
                            </div>
                            <div class="mt-2 text-lg font-semibold text-primary-600 dark:text-primary-400">
                                Rp {{ number_format($service->price, 0, ',', '.') }}
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Kolom Kanan: Keranjang -->
            <div class="w-full lg:w-[450px] shrink-0">
                <div class="sticky top-6 space-y-4">
                    <!-- Pencarian Pelanggan -->
                    <div class="p-4 bg-white border rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Pelanggan
                        </label>

                        @if ($customer_id)
                            @php $customer = \App\Models\Customer::find($customer_id); @endphp
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                                <div>
                                    <div class="font-medium">{{ $customer->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $customer->phone }}</div>
                                </div>
                                <button wire:click="clearCustomer" class="text-danger-600 hover:text-danger-800">
                                    <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                                </button>
                            </div>
                        @else
                            <x-filament::input.wrapper>
                                <x-filament::input type="text" placeholder="Cari nama atau no telepon..."
                                    wire:model.live.debounce.300ms="customer_search" />
                            </x-filament::input.wrapper>

                            @if ($customers->count() > 0)
                                <div
                                    class="mt-2 border rounded-lg divide-y dark:border-gray-700 max-h-48 overflow-y-auto">
                                    @foreach ($customers as $customer)
                                        <button wire:click="selectCustomer({{ $customer->id }})"
                                            class="w-full px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <div class="font-medium">{{ $customer->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $customer->phone }}</div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Keranjang Belanja -->
                    <div class="p-4 bg-white border rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <h3 class="mb-3 text-lg font-semibold">Keranjang Belanja</h3>

                        @if (empty($cart))
                            <div class="py-8 text-center text-gray-500">
                                <x-filament::icon icon="heroicon-m-shopping-cart" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                                <p>Keranjang masih kosong</p>
                            </div>
                        @else
                            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                                @foreach ($cart as $item)
                                    <div
                                        class="flex items-center justify-between p-2 bg-gray-50 rounded-lg dark:bg-gray-700">
                                        <div class="flex-1">
                                            <div class="font-medium">{{ $item['name'] }}</div>
                                            <div class="text-sm text-gray-500">
                                                Rp {{ number_format($item['price'], 0, ',', '.') }} x
                                                <input type="number" value="{{ $item['quantity'] }}"
                                                    wire:change="updateQuantity('{{ $item['id'] }}', $event.target.value)"
                                                    min="1"
                                                    class="w-16 px-1 py-0.5 text-sm border rounded dark:bg-gray-600 dark:border-gray-500"
                                                    {{ $item['type'] == 'product' ? 'max=' . \App\Models\Product::find($item['item_id'])?->stock : '' }}>
                                                @if ($item['type'] == 'product')
                                                    @php $product = \App\Models\Product::find($item['item_id']); @endphp
                                                    @if ($product && $product->stock < $item['quantity'])
                                                        <span class="ml-1 text-xs text-danger-600">(Stok:
                                                            {{ $product->stock }})</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="font-semibold text-right">
                                                Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                            </div>
                                                <button wire:click="removeFromCart('{{ $item['id'] }}')"
                                                class="text-danger-600 hover:text-danger-800">
                                                <x-filament::icon icon="heroicon-m-trash" class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Ringkasan -->
                            <div class="mt-4 space-y-2 border-t pt-4 dark:border-gray-700">
                                <div class="flex justify-between text-sm">
                                    <span>Subtotal:</span>
                                    <span class="font-medium">Rp
                                        {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm">Diskon:</span>
                                    <x-filament::input.wrapper class="w-32">
                                        <x-filament::input type="number" wire:model.live="discount" min="0"
                                            step="1000" class="text-right" placeholder="0" />
                                    </x-filament::input.wrapper>
                                </div>

                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total:</span>
                                    <span>Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                                </div>

                                <div class="pt-2 space-y-2">
                                    <div>
                                        <label class="block mb-1 text-sm font-medium">Metode Pembayaran</label>
                                        <select wire:model.live="payment_method"
                                            class="w-full border-gray-300 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600">
                                            <option value="cash">Tunai</option>
                                            <option value="qris">QRIS</option>
                                            <option value="debit">Kartu Debit</option>
                                            <option value="credit">Kartu Kredit</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block mb-1 text-sm font-medium">Jumlah Dibayar</label>
                                        <x-filament::input.wrapper>
                                            <x-filament::input type="number" wire:model.live="payment_amount"
                                                min="0" step="1000" class="text-right" placeholder="0" />
                                        </x-filament::input.wrapper>
                                    </div>

                                    @if ($payment_amount > 0)
                                        <div class="flex justify-between text-sm">
                                            <span>Kembalian:</span>
                                            <span class="font-medium">Rp
                                                {{ number_format($this->change, 0, ',', '.') }}</span>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block mb-1 text-sm font-medium">Catatan (opsional)</label>
                                        <textarea wire:model="notes" rows="2"
                                            class="w-full border-gray-300 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600"
                                            placeholder="Catatan untuk transaksi..."></textarea>
                                    </div>
                                </div>

                                <x-filament::button wire:click="processPayment" wire:loading.attr="disabled"
                                    color="success" size="lg" class="w-full mt-4">
                                    <span wire:loading.remove wire:target="processPayment">
                                        <x-filament::icon icon="heroicon-m-credit-card" class="w-5 h-5 mr-2 inline-block" />
                                        Proses Pembayaran
                                    </span>
                                    <span wire:loading wire:target="processPayment">
                                        <x-filament::loading-indicator class="w-5 h-5 mr-2 inline-block" />
                                        Memproses...
                                    </span>
                                </x-filament::button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
