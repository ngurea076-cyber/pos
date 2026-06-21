<div wire:click.self="closeRecord" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-slate-950/50 p-4">
    <section class="my-auto w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0"><h2 class="truncate text-xl font-bold">{{ $viewedRecord->product?->name ?? 'Take-out record' }}</h2><p class="text-sm text-slate-500">Reseller take-out</p></div>
            <button wire:click="closeRecord" class="text-slate-400"><i data-lucide="x" class="size-5"></i></button>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-5">
            <div><small class="block text-slate-500">Action date</small><b>{{ $viewedRecord->happened_at->format('d M Y') }}</b><span class="block text-xs text-slate-500">{{ $viewedRecord->happened_at->format('H:i') }}</span></div>
            <div><small class="block text-slate-500">Supplier</small><b>{{ $viewedRecord->supplier?->name ?? '—' }}</b></div>
            <div><small class="block text-slate-500">Quantity</small><b>{{ $viewedRecord->quantity }}</b></div>
            <div><small class="block text-slate-500">Reseller</small><b>{{ $viewedRecord->reseller?->name ?? '—' }}</b></div>
            <div><small class="block text-slate-500">Recorded by</small><b>{{ $viewedRecord->user?->name ?? '—' }}</b></div>
        </div>

        @if($viewedRecord->notes)<div class="mt-4"><span class="label">Notes</span><p class="rounded-xl border border-slate-200 p-3 text-sm">{{ $viewedRecord->notes }}</p></div>@endif

        <div class="mt-5">
            <h3 class="mb-2 font-bold">Involved serial</h3>
            @if($viewedRecord->serial)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3"><code class="min-w-0 truncate text-sm">{{ $viewedRecord->serial->serial }}</code><span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-xs font-medium">{{ $states[$viewedRecord->serial->status] ?? $viewedRecord->serial->status }}</span></div>
            @else
                <div class="rounded-xl border border-slate-200 p-6 text-center text-sm text-slate-500">No serial code is linked to this record.</div>
            @endif
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
            <button wire:click="closeRecord" class="btn-muted">Close</button>
            @if($viewedRecord->serial?->status === 'with_reseller')
                <button wire:click="returnTakeoutSerial({{ $viewedRecord->serial->id }})" wire:confirm="Mark this product as returned to stock?" class="btn-muted"><i data-lucide="rotate-ccw" class="mr-2 size-4"></i>Returned</button>
                <button wire:click="sellTakeoutSerial({{ $viewedRecord->serial->id }})" class="btn-primary"><i data-lucide="shopping-cart" class="mr-2 size-4"></i>Sold</button>
            @endif
        </div>
    </section>
</div>
