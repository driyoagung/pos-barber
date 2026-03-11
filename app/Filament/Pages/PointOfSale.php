<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class PointOfSale extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'POS / Kasir';

    protected static ?string $title = 'Point of Sale';

    protected static ?string $slug = 'pos';

    protected static ?int $navigationSort = 1;

    // Penting: Gunakan view ini
    protected static string $view = 'filament.pages.point-of-sale';

    public $cart = [];
    public $customer_id = null;
    public $customer_search = '';
    public $customers = [];
    public $payment_method = 'cash';
    public $payment_amount = 0;
    public $discount = 0;
    public $notes = '';
    public $search_product = '';

    public function mount()
    {
        $this->cart = session()->get('pos_cart', []);
        $this->loadCustomers();
    }

    public function loadCustomers()
    {
        if (strlen($this->customer_search) > 2) {
            $this->customers = Customer::where('name', 'like', '%' . $this->customer_search . '%')
                ->orWhere('phone', 'like', '%' . $this->customer_search . '%')
                ->limit(5)
                ->get();
        } else {
            $this->customers = collect([]);
        }
    }

    public function selectCustomer($customerId)
    {
        $this->customer_id = $customerId;
        $this->customer_search = '';
        $this->customers = collect([]);
    }

    public function clearCustomer()
    {
        $this->customer_id = null;
    }

    public function addService($serviceId)
    {
        $service = Service::find($serviceId);
        if (!$service)
            return;

        $cartItem = [
            'id' => uniqid(),
            'type' => 'service',
            'item_id' => $service->id,
            'name' => $service->name,
            'price' => $service->price,
            'quantity' => 1,
            'subtotal' => $service->price,
        ];

        $this->cart[] = $cartItem;
        $this->saveCart();

        Notification::make()
            ->title('Layanan ditambahkan')
            ->success()
            ->send();
    }

    public function addProduct($productId)
    {
        $product = Product::find($productId);
        if (!$product)
            return;

        // Cek stok
        if ($product->stock < 1) {
            Notification::make()
                ->title('Stok tidak mencukupi')
                ->danger()
                ->send();
            return;
        }

        // Cek apakah produk sudah ada di cart
        $existingIndex = collect($this->cart)->search(function ($item) use ($productId) {
            return $item['type'] == 'product' && $item['item_id'] == $productId;
        });

        if ($existingIndex !== false) {
            // Cek stok cukup untuk nambah
            $newQuantity = $this->cart[$existingIndex]['quantity'] + 1;
            if ($product->stock < $newQuantity) {
                Notification::make()
                    ->title('Stok tidak mencukupi')
                    ->danger()
                    ->send();
                return;
            }

            // Update quantity jika sudah ada
            $this->cart[$existingIndex]['quantity'] = $newQuantity;
            $this->cart[$existingIndex]['subtotal'] = $this->cart[$existingIndex]['price'] * $this->cart[$existingIndex]['quantity'];
        } else {
            // Tambah baru
            $cartItem = [
                'id' => uniqid(),
                'type' => 'product',
                'item_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'subtotal' => $product->price,
            ];
            $this->cart[] = $cartItem;
        }

        $this->saveCart();

        Notification::make()
            ->title('Produk ditambahkan')
            ->success()
            ->send();
    }

    public function updateQuantity($cartId, $quantity)
    {
        $index = collect($this->cart)->search(fn($item) => $item['id'] == $cartId);

        if ($index !== false) {
            $item = $this->cart[$index];

            // Validasi stok untuk produk
            if ($item['type'] == 'product') {
                $product = Product::find($item['item_id']);
                if ($product && $product->stock < $quantity) {
                    Notification::make()
                        ->title('Stok tidak mencukupi. Maksimal ' . $product->stock)
                        ->danger()
                        ->send();
                    return;
                }
            }

            $this->cart[$index]['quantity'] = max(1, intval($quantity));
            $this->cart[$index]['subtotal'] = $this->cart[$index]['price'] * $this->cart[$index]['quantity'];
            $this->saveCart();
        }
    }

    public function removeFromCart($cartId)
    {
        $this->cart = array_values(array_filter($this->cart, fn($item) => $item['id'] != $cartId));
        $this->saveCart();

        Notification::make()
            ->title('Item dihapus dari keranjang')
            ->info()
            ->send();
    }

    public function saveCart()
    {
        session()->put('pos_cart', $this->cart);
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->discount = 0;
        $this->payment_amount = 0;
        $this->notes = '';
        $this->customer_id = null;
        session()->forget('pos_cart');

        Notification::make()
            ->title('Keranjang dikosongkan')
            ->info()
            ->send();
    }

    #[Computed]
    public function subtotal()
    {
        return collect($this->cart)->sum('subtotal');
    }

    #[Computed]
    public function total()
    {
        return max(0, $this->subtotal() - $this->discount);
    }

    #[Computed]
    public function change()
    {
        return max(0, $this->payment_amount - $this->total());
    }

    public function processPayment()
    {
        // Validasi cart tidak kosong
        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang belanja masih kosong')
                ->danger()
                ->send();
            return;
        }

        // Validasi pembayaran
        if ($this->payment_amount < $this->total()) {
            Notification::make()
                ->title('Jumlah pembayaran kurang dari total')
                ->danger()
                ->send();
            return;
        }

        DB::beginTransaction();

        try {
            // Buat nomor invoice
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(Transaction::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // Simpan transaksi
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $this->customer_id,
                'user_id' => Auth::id(),
                'transaction_date' => now(),
                'subtotal' => $this->subtotal(),
                'discount' => $this->discount,
                'tax' => 0,
                'total' => $this->total(),
                'payment_method' => $this->payment_method,
                'payment_amount' => $this->payment_amount,
                'change_amount' => $this->change(),
                'status' => 'completed',
                'notes' => $this->notes,
            ]);

            // Simpan item transaksi dan update stok
            foreach ($this->cart as $item) {
                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'item_type' => $item['type'],
                    'item_id' => $item['item_id'],
                    'item_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Update stok jika produk
                if ($item['type'] == 'product') {
                    $product = Product::find($item['item_id']);
                    if ($product) {
                        $product->stock -= $item['quantity'];
                        $product->save();
                    }
                }
            }

            // Update poin customer jika ada
            if ($this->customer_id) {
                $customer = Customer::find($this->customer_id);
                if ($customer) {
                    $points = floor($this->total() / 10000);
                    $customer->points += $points;
                    $customer->save();
                }
            }

            DB::commit();

            // Bersihkan cart
            $this->clearCart();

            // Tampilkan notifikasi sukses
            Notification::make()
                ->title('Transaksi berhasil!')
                ->body('Invoice: ' . $invoiceNumber)
                ->success()
                ->send();

            // Redirect ke halaman detail transaksi
            return redirect()->route('filament.admin.resources.transactions.view', $transaction);

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Terjadi kesalahan!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    #[Computed]
    public function services()
    {
        return Service::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        $query = Product::where('is_active', true);

        if ($this->search_product) {
            $query->where('name', 'like', '%' . $this->search_product . '%');
        }

        return $query->orderBy('name')
            ->limit(12)
            ->get();
    }

    protected function getForms(): array
    {
        return [];
    }
}