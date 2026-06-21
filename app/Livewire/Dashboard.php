<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Services\NotificationService;
use Livewire\Component;

class Dashboard extends Component
{
    public string $serialSearch = '';

    public function searchSerial(): void
    {
        $this->validate(['serialSearch' => ['required', 'string', 'max:255']]);
        $serial = ProductSerial::where('serial', trim($this->serialSearch))->first();

        if (! $serial) {
            $this->addError('serialSearch', 'No serial code matched your search.');
            return;
        }

        $this->redirectRoute('inventory.serial-history', ['serial' => $serial->id], navigate: true);
    }

    public function render()
    {
        if (! auth()->user()->isAdmin()) {
            return view('livewire.dashboard', [
                'recentNotifications' => app(NotificationService::class)->recent(auth()->user(), 5),
            ])->layout('layouts.app', ['title' => 'Dashboard']);
        }

        $start = now()->subDays(29)->startOfDay();
        $daily = Order::where('is_invalid', false)->where('created_at', '>=', $start)->selectRaw('DATE(created_at) day, SUM(total) total')->groupBy('day')->pluck('total', 'day');

        return view('livewire.dashboard', [
            'todaySales' => Order::where('is_invalid', false)->whereDate('created_at', today())->sum('total'),
            'todayOrders' => Order::where('is_invalid', false)->whereDate('created_at', today())->count(),
            'monthSales' => Order::where('is_invalid', false)->where('created_at', '>=', $start)->sum('total'),
            'productCount' => Product::count(),
            'lowStock' => Product::whereColumn('stock', '<=', 'reorder_level')->limit(5)->get(),
            'topProducts' => OrderItem::where('created_at', '>=', $start)->selectRaw('name_snapshot, SUM(quantity) quantity')->groupBy('name_snapshot')->orderByDesc('quantity')->limit(5)->get(),
            'daily' => $daily,
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
