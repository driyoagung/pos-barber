<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transaksi')
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('No. Invoice')
                            ->default('INV-' . date('Ymd') . '-' . str_pad(Transaction::count() + 1, 4, '0', STR_PAD_LEFT))
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        DateTimePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->default(now())
                            ->required()
                            ->displayFormat('d/m/Y H:i'),
                        Select::make('customer_id')
                            ->label('Pelanggan')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Pelanggan')
                                    ->required(),
                                TextInput::make('phone')
                                    ->label('No. Telepon')
                                    ->tel(),
                            ])
                            ->nullable(),
                        Select::make('user_id')
                            ->label('Kasir')
                            ->relationship('user', 'name')
                            ->default(auth()->id())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                    ])->columns(2),

                Section::make('Item Transaksi')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('item_type')
                                    ->label('Tipe')
                                    ->options([
                                        'service' => 'Layanan',
                                        'product' => 'Produk',
                                    ])
                                    ->live()
                                    ->required()
                                    ->columnSpan(1),
                                Select::make('item_id')
                                    ->label('Item')
                                    ->options(function (Get $get) {
                                        $type = $get('item_type');
                                        if ($type === 'service') {
                                            return \App\Models\Service::where('is_active', true)->pluck('name', 'id');
                                        } elseif ($type === 'product') {
                                            return \App\Models\Product::where('is_active', true)->pluck('name', 'id');
                                        }
                                        return [];
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $type = $get('item_type');
                                        if ($type === 'service' && $state) {
                                            $service = \App\Models\Service::find($state);
                                            if ($service) {
                                                $set('item_name', $service->name);
                                                $set('price', $service->price);
                                            }
                                        } elseif ($type === 'product' && $state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('item_name', $product->name);
                                                $set('price', $product->price);
                                            }
                                        }
                                    })
                                    ->required()
                                    ->columnSpan(2),
                                Hidden::make('item_name'),
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $price = $get('price') ?? 0;
                                        $set('subtotal', $price * $state);
                                    })
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $quantity = $get('quantity') ?? 1;
                                        $set('subtotal', $state * $quantity);
                                    })
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                            ])
                            ->columns(8)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            }),
                    ]),

                Section::make('Ringkasan Pembayaran')
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        TextInput::make('discount')
                            ->label('Diskon')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            }),
                        TextInput::make('tax')
                            ->label('Pajak')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateTotals($get, $set);
                            }),
                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Tunai',
                                'qris' => 'QRIS',
                                'debit' => 'Kartu Debit',
                                'credit' => 'Kartu Kredit',
                            ])
                            ->default('cash')
                            ->live()
                            ->required(),
                        TextInput::make('payment_amount')
                            ->label('Jumlah Dibayar')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $total = $get('total') ?? 0;
                                $payment = $get('payment_amount') ?? 0;
                                $set('change_amount', max(0, $payment - $total));
                            })
                            ->required(),
                        TextInput::make('change_amount')
                            ->label('Kembalian')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated()
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    protected static function updateTotals(Get $get, Set $set): void
    {
        // Hitung subtotal dari items
        $items = $get('items') ?? [];
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += floatval($item['subtotal'] ?? 0);
        }

        $set('subtotal', $subtotal);

        // Hitung total setelah diskon dan pajak
        $discount = floatval($get('discount') ?? 0);
        $tax = floatval($get('tax') ?? 0);
        $total = $subtotal - $discount + $tax;

        $set('total', max(0, $total));

        // Update kembalian
        $payment = floatval($get('payment_amount') ?? 0);
        $set('change_amount', max(0, $payment - $total));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Pelanggan')
                    ->default('- Umum -')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'success',
                        'qris' => 'info',
                        'debit', 'credit' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'qris' => 'QRIS',
                        'debit' => 'Debit',
                        'credit' => 'Kredit',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Selesai',
                        'pending' => 'Pending',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('transaction_date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('transaction_date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}