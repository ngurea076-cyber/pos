<div wire:click.self="closeRecord" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-slate-950/50 p-4">
    <section class="my-auto w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0"><h2 class="truncate text-xl font-bold">{{ $viewedRecord->product?->name ?? 'Sold product' }}</h2><p class="text-sm text-slate-500">Sale</p></div>
            <button wire:click="closeRecord" class="text-slate-400"><i data-lucide="x" class="size-5"></i></button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-5">
            <div><small class="block text-slate-500">Action date</small><b>{{ $viewedRecord->happened_at->format('d M Y') }}</b><span class="block text-xs text-slate-500">{{ $viewedRecord->happened_at->format('H:i') }}</span></div>
            <div><small class="block text-slate-500">Supplier</small><b>{{ $viewedRecord->supplier?->name ?? '—' }}</b></div>
            <div><small class="block text-slate-500">Quantity</small><b>{{ $viewedRecord->quantity }}</b></div>
            <div><small class="block text-slate-500">Reseller</small><b>{{ $viewedRecord->reseller?->name ?? '—' }}</b></div>
            <div><small class="block text-slate-500">Recorded by</small><b>{{ $viewedRecord->user?->name ?? '—' }}</b></div>
        </div>

        @if($viewedRecord->serial?->order)
            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm">
                <span class="min-w-0"><small class="block text-brand-700">Order ID</small><b class="block truncate">{{ $viewedRecord->serial->order->order_number }}</b></span>
                <a wire:navigate href="{{ route('orders', ['order' => $viewedRecord->serial->order]) }}" class="btn-primary shrink-0 px-3 py-2 text-xs">View order <i data-lucide="arrow-right" class="ml-1 size-3.5"></i></a>
            </div>
        @endif

        @if($viewedRecord->notes)<div class="mt-4"><span class="label">Notes</span><p class="rounded-xl border border-slate-200 p-3 text-sm">{{ $viewedRecord->notes }}</p></div>@endif

        <div class="mt-5">
            <div class="mb-2 flex justify-between"><h3 class="font-bold">Involved serial</h3><span class="text-xs text-slate-500">{{ $viewedSerials->count() }} total</span></div>
            <div class="max-h-80 divide-y overflow-y-auto rounded-xl border border-slate-200">
                @forelse($viewedSerials as $serial)
                    <div class="flex items-center justify-between gap-3 p-3"><code class="min-w-0 truncate text-sm">{{ $serial->serial }}</code><span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium">{{ $states[$serial->status] ?? $serial->status }}</span></div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">No serial code is linked to this sale.</div>
                @endforelse
            </div>
        </div>

        <div class="mt-6 flex justify-end"><button wire:click="closeRecord" class="btn-primary">Close</button></div>
    </section>
</div>
