<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationFeedController extends Controller
{
    public function __invoke(NotificationService $service): JsonResponse
    {
        $stock = $service->lowStock(auth()->user())->where('is_read', false)->map(fn ($notification) => [
            'key' => $notification->key,
            'title' => 'Low stock: '.$notification->product->name,
            'body' => $notification->product->stock.' piece'.($notification->product->stock === 1 ? '' : 's').' remaining.',
            'url' => route('notifications'),
        ]);
        $takeouts = $service->overdueTakeouts()->map(fn ($notification) => [
            'key' => 'takeout:'.$notification->serial->id.':reminder:'.$notification->reminder_number,
            'title' => 'Reseller update overdue',
            'body' => $notification->serial->product?->name.' ('.$notification->serial->serial.') needs a sold or returned update.',
            'url' => route('inventory.serial-history', ['serial' => $notification->serial]),
        ]);

        return response()->json(['notifications' => $stock->concat($takeouts)->values()]);
    }
}
