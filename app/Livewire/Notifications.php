<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductSerial;
use App\Services\NotificationService;
use App\Services\ResellerTakeoutService;
use Livewire\Component;

class Notifications extends Component
{
    public function sell(int $serialId): void
    {
        $serial = ProductSerial::whereKey($serialId)->where('status', 'with_reseller')->firstOrFail();
        $this->redirectRoute('pos', ['reseller_serial' => $serial->id], navigate: true);
    }

    public function markReturned(int $serialId): void
    {
        app(ResellerTakeoutService::class)->returnToStock($serialId, auth()->id());
        $this->dispatch('notification-read');
        session()->flash('status', 'Product returned to stock.');
    }

    public function markStockRead(int $productId): void
    {
        $product = Product::findOrFail($productId);
        if (app(NotificationService::class)->markStockRead(auth()->user(), $product)) $this->dispatch('notification-read');
    }

    public function render()
    {
        $service = app(NotificationService::class);

        return view('livewire.notifications', [
            'stockNotifications' => $service->lowStock(auth()->user()),
            'takeoutNotifications' => $service->overdueTakeouts(),
            'resolvedTakeouts' => $service->resolvedTakeouts(),
        ])->layout('layouts.app', ['title' => 'Notifications']);
    }
}
