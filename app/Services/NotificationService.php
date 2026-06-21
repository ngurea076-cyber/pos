<?php

namespace App\Services;

use App\Models\NotificationRead;
use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    public function lowStock(User $user): Collection
    {
        $products = Product::where('stock', '<', 3)->orderBy('stock')->orderBy('name')->get();
        $keys = $products->map(fn (Product $product) => $this->stockKey($product))->all();
        $readKeys = NotificationRead::where('user_id', $user->id)->where('notification_type', 'low_stock')->whereIn('notification_key', $keys)->pluck('notification_key')->all();

        return $products->map(fn (Product $product) => (object) [
            'product' => $product,
            'key' => $this->stockKey($product),
            'is_read' => in_array($this->stockKey($product), $readKeys, true),
        ]);
    }

    public function overdueTakeouts(): Collection
    {
        return ProductSerial::with(['product', 'reseller', 'takeout'])
            ->where('status', 'with_reseller')
            ->whereNotNull('takeout_id')
            ->get()
            ->map(function (ProductSerial $serial) {
                $takenAt = $serial->takeout?->created_at ?? $serial->updated_at;
                $dueAt = $takenAt->copy()->addDay();
                if (now()->lt($dueAt)) return null;
                $hoursOverdue = $dueAt->diffInHours(now());

                return (object) [
                    'serial' => $serial,
                    'taken_at' => $takenAt,
                    'due_at' => $dueAt,
                    'reminder_number' => intdiv((int) $hoursOverdue, 6) + 1,
                ];
            })
            ->filter()
            ->sortByDesc('due_at')
            ->values();
    }

    public function unreadCount(User $user): int
    {
        return $this->lowStock($user)->where('is_read', false)->count() + $this->overdueTakeouts()->count();
    }

    public function recent(User $user, int $limit = 5): Collection
    {
        $stock = $this->lowStock($user)->where('is_read', false)->map(fn ($notification) => (object) [
            'type' => 'low_stock',
            'title' => $notification->product->name,
            'message' => $notification->product->stock.' piece'.($notification->product->stock === 1 ? '' : 's').' remaining',
            'occurred_at' => $notification->product->updated_at,
        ]);
        $takeouts = $this->overdueTakeouts()->map(fn ($notification) => (object) [
            'type' => 'reseller_takeout',
            'title' => $notification->serial->product?->name ?? 'Reseller take-out',
            'message' => 'Reseller update overdue · '.$notification->serial->serial,
            'occurred_at' => $notification->due_at->copy()->addHours(($notification->reminder_number - 1) * 6),
        ]);

        return $stock->concat($takeouts)->sortByDesc('occurred_at')->take($limit)->values();
    }

    public function resolvedTakeouts(): Collection
    {
        return InventoryRecord::with(['product', 'serial.order', 'reseller', 'user'])
            ->where(function ($query) {
                $query->where('type', 'reseller_return')
                    ->orWhere(function ($sale) {
                        $sale->where('type', 'sale')->whereHas('serial', fn ($serial) => $serial->whereNotNull('reseller_id')->whereNotNull('takeout_id'));
                    });
            })
            ->latest('happened_at')
            ->limit(50)
            ->get();
    }

    public function markStockRead(User $user, Product $product): bool
    {
        $key = $this->stockKey($product);
        $read = NotificationRead::firstOrCreate(
            ['user_id' => $user->id, 'notification_type' => 'low_stock', 'notification_key' => $key],
            ['read_at' => now()]
        );

        return $read->wasRecentlyCreated;
    }

    private function stockKey(Product $product): string
    {
        return 'product:'.$product->id.':stock:'.$product->stock;
    }
}
