<div class="space-y-6">
    @php($unreadStocks=$stockNotifications->where('is_read', false))
    @php($readStocks=$stockNotifications->where('is_read', true))

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h1 class="text-3xl font-bold text-ink">Notifications</h1><p class="text-sm text-slate-500">Stock alerts and overdue reseller take-out reminders.</p></div>
        <button type="button" onclick="enablePwaNotifications(this)" class="btn-muted shrink-0"><i data-lucide="bell" class="mr-2 size-4"></i>Allow notifications</button>
    </div>

    <section class="card p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between"><div><h2 class="font-bold">Low stock</h2><p class="text-xs text-slate-500">Products with fewer than 3 pieces remaining.</p></div><span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">{{ $unreadStocks->count() }} unread</span></div>
        <div class="space-y-2">
            @forelse($unreadStocks as $notification)
                <article class="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50/40 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><b class="truncate">{{ $notification->product->name }}</b><span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Low stock</span></div><p class="mt-1 text-sm text-slate-500"><b class="text-red-600">{{ $notification->product->stock }}</b> piece{{ $notification->product->stock === 1 ? '' : 's' }} remaining</p></div>
                    <button wire:click="markStockRead({{ $notification->product->id }})" class="btn-muted shrink-0 px-3 py-2 text-xs"><i data-lucide="check" class="mr-1.5 size-4"></i>Mark as read</button>
                </article>
            @empty
                <div class="py-10 text-center text-sm text-slate-500">No unread low-stock alerts.</div>
            @endforelse
        </div>
    </section>

    <section class="card p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between"><div><h2 class="font-bold">Reseller take-out updates</h2><p class="text-xs text-slate-500">Alerts begin after 24 hours and renew every 6 hours until resolved.</p></div><span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">{{ $takeoutNotifications->count() }} pending</span></div>
        <div class="space-y-3">
            @forelse($takeoutNotifications as $notification)
                @php($serial=$notification->serial)
                <article class="rounded-xl border border-red-200 bg-red-50/40 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><b class="truncate">{{ $serial->product?->name ?? 'Deleted product' }}</b><span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Reminder {{ $notification->reminder_number }}</span></div><p class="mt-1 font-mono text-xs text-slate-500">{{ $serial->serial }}</p><p class="mt-1 text-xs text-slate-500">Reseller: <b>{{ $serial->reseller?->name ?? '—' }}</b> · Due {{ $notification->due_at->format('d M Y, H:i') }}</p></div>
                        <div class="flex shrink-0 gap-2"><button wire:click="markReturned({{ $serial->id }})" wire:confirm="Mark this product as returned to stock?" class="btn-muted px-3 py-2 text-xs"><i data-lucide="rotate-ccw" class="mr-1.5 size-4"></i>Returned</button><button wire:click="sell({{ $serial->id }})" class="btn-primary px-3 py-2 text-xs"><i data-lucide="shopping-cart" class="mr-1.5 size-4"></i>Sold</button></div>
                    </div>
                </article>
            @empty
                <div class="py-10 text-center"><i data-lucide="check" class="mx-auto size-8 text-brand-600"></i><p class="mt-3 font-semibold">No overdue reseller take-outs.</p></div>
            @endforelse
        </div>
    </section>

    <section class="card p-4 sm:p-5">
        <div class="mb-4"><h2 class="font-bold">Read &amp; resolved</h2><p class="text-xs text-slate-500">Completed notification history.</p></div>
        <div class="divide-y rounded-xl border border-slate-200">
            @foreach($readStocks as $notification)
                <article class="flex items-center justify-between gap-3 p-4"><div class="min-w-0"><b class="block truncate">{{ $notification->product->name }}</b><p class="text-xs text-slate-500">Low-stock alert · {{ $notification->product->stock }} remaining</p></div><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Read</span></article>
            @endforeach
            @foreach($resolvedTakeouts as $record)
                <article class="flex items-center justify-between gap-3 p-4"><div class="min-w-0"><b class="block truncate">{{ $record->product?->name ?? 'Deleted product' }}</b><p class="truncate text-xs text-slate-500">{{ $record->serial?->serial ?? 'No serial' }} · {{ $record->type === 'sale' ? 'Sold' : 'Returned to stock' }} · {{ $record->happened_at->format('d M Y, H:i') }}</p></div>@if($record->type === 'sale' && $record->serial?->order)<a wire:navigate href="{{ route('orders', ['order'=>$record->serial->order]) }}" class="shrink-0 text-xs font-semibold text-brand-700 hover:underline">{{ $record->serial->order->order_number }}</a>@else<span class="rounded-full bg-brand-50 px-2 py-1 text-xs font-semibold text-brand-700">Resolved</span>@endif</article>
            @endforeach
            @if($readStocks->isEmpty() && $resolvedTakeouts->isEmpty())<div class="p-10 text-center text-sm text-slate-500">No read or resolved notifications yet.</div>@endif
        </div>
    </section>
</div>
