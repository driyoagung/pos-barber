<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->month;
        $thisYear = now()->year;

        $todaySales = Transaction::whereDate('transaction_date', $today)
            ->where('status', 'completed')
            ->sum('total');

        $monthSales = Transaction::whereMonth('transaction_date', $thisMonth)
            ->whereYear('transaction_date', $thisYear)
            ->where('status', 'completed')
            ->sum('total');

        $totalCustomers = Customer::count();

        $popularService = Transaction::join('transaction_items', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transaction_items.item_type', 'service')
            ->select('transaction_items.item_name', DB::raw('count(*) as total'))
            ->groupBy('transaction_items.item_name')
            ->orderByDesc('total')
            ->first();

        return [
            Stat::make('Penjualan Hari Ini', 'Rp ' . number_format($todaySales, 0, ',', '.'))
                ->description(now()->format('d M Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Penjualan Bulan Ini', 'Rp ' . number_format($monthSales, 0, ',', '.'))
                ->description('Bulan ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Total Pelanggan', number_format($totalCustomers, 0, ',', '.'))
                ->description('Pelanggan terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),

            Stat::make('Layanan Terpopuler', $popularService->item_name ?? '-')
                ->description($popularService ? $popularService->total . ' kali transaksi' : 'Belum ada data')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
        ];
    }
}