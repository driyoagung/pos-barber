<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class RecentTransactions extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::where('status', 'completed')
                    ->latest()
                    ->limit(10)
            )
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
                    ->default('- Umum -'),
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
                TextColumn::make('user.name')
                    ->label('Kasir'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn(Transaction $record): string => route('filament.admin.resources.transactions.view', $record))
                    ->icon('heroicon-m-eye'),
            ]);
    }
}