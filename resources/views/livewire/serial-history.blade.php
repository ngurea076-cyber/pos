<div class="mx-auto max-w-4xl space-y-6">
    @php($states=['sold'=>'Sold','in_stock'=>'In stock','with_reseller'=>'Take-out','returned_to_supplier'=>'Returned to supplier'])
    @php($labels=['stock_intake'=>'Stock intake','reseller_takeout'=>'Reseller take-out','reseller_return'=>'Reseller return','wholesaler_return'=>'Supplier return','customer_return'=>'Customer return','sale'=>'Sale'])
    @php($movementStates=['stock_intake'=>'In stock','reseller_takeout'=>'Take-out','reseller_return'=>'In stock','wholesaler_return'=>'Returned to supplier','customer_return'=>'In stock','sale'=>'Sold'])

    <div class="flex items-start gap-3">
        <a wire:navigate href="{{ route('inventory.section',['section'=>'records']) }}" class="btn-muted px-3"><i data-lucide="arrow-left" class="size-4"></i></a>
        <div class="min-w-0"><h1 class="truncate text-2xl font-bold text-ink sm:text-3xl">{{ $serial->product->name }}</h1><p class="font-mono text-sm text-slate-500">{{ $serial->serial }}</p></div>
    </div>

    <section class="card grid grid-cols-2 gap-4 p-5 text-sm sm:grid-cols-4">
        <div><span class="block text-xs text-slate-500">Current state</span><b class="text-brand-700">{{ $states[$serial->status] ?? $serial->status }}</b></div>
        <div><span class="block text-xs text-slate-500">Supplier</span><b>{{ $serial->supplier?->name ?? '—' }}</b></div>
        <div><span class="block text-xs text-slate-500">Prefix</span><b>{{ $serial->prefix ?: '—' }}</b></div>
        <div><span class="block text-xs text-slate-500">Added</span><b>{{ $serial->created_at->format('d M Y') }}</b></div>
    </section>

    @if($serial->status === 'with_reseller')
        <section class="card flex flex-col gap-3 border-amber-200 bg-amber-50/40 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="font-bold">Resolve reseller take-out</h2><p class="text-xs text-slate-500">Mark this serial as sold or returned to available stock.</p></div>
            <div class="flex shrink-0 gap-2"><button wire:click="returnTakeout" wire:confirm="Mark this product as returned to stock?" class="btn-muted"><i data-lucide="rotate-ccw" class="mr-2 size-4"></i>Returned</button><button wire:click="sellTakeout" class="btn-primary"><i data-lucide="shopping-cart" class="mr-2 size-4"></i>Sold</button></div>
        </section>
    @endif

    <section class="card p-5">
        <div class="mb-5"><h2 class="font-bold">Movement history</h2><p class="text-xs text-slate-500">Chronological activity for this serial code.</p></div>
        <div class="relative space-y-0 before:absolute before:bottom-4 before:left-[15px] before:top-4 before:w-px before:bg-slate-200">
            @forelse($records as $record)
                <article class="relative flex gap-4 pb-6">
                    <span class="z-10 mt-1 grid size-8 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700"><i data-lucide="{{ $record->type==='sale'?'shopping-cart':($record->type==='stock_intake'?'arrow-down-to-line':($record->type==='reseller_takeout'?'send':($record->type==='wholesaler_return'?'undo-2':'rotate-ccw'))) }}" class="size-4"></i></span>
                    <div class="min-w-0 flex-1 rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2"><div><b>{{ $labels[$record->type] ?? $record->type }}</b><p class="text-xs text-slate-500">State after movement: {{ $movementStates[$record->type] ?? '—' }}</p></div><time class="text-xs font-medium text-slate-500">{{ $record->happened_at->format('d M Y, H:i') }}</time></div>
                        <div class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                            <div><span class="block text-xs text-slate-500">Responsible person</span><b>{{ $record->user?->name ?? '—' }}</b></div>
                            <div><span class="block text-xs text-slate-500">Supplier</span><b>{{ $record->supplier?->name ?? '—' }}</b></div>
                            <div>
                                <span class="block text-xs text-slate-500">{{ $record->type === 'sale' ? 'Order ID' : 'Reseller' }}</span>
                                @if($record->type === 'sale' && $record->sale_order_number)
                                    <a wire:navigate href="{{ route('orders', ['order' => $record->sale_order_id]) }}" class="inline-flex items-center font-semibold text-brand-700 hover:underline">{{ $record->sale_order_number }}<i data-lucide="arrow-right" class="ml-1 size-3.5"></i></a>
                                @else
                                    <b>{{ $record->type === 'sale' ? '—' : ($record->reseller?->name ?? '—') }}</b>
                                @endif
                            </div>
                        </div>
                        @if($record->notes)<p class="mt-3 rounded-lg bg-slate-50 p-2 text-sm text-slate-600">{{ $record->notes }}</p>@endif
                    </div>
                </article>
            @empty
                <div class="py-12 text-center text-sm text-slate-500">No movements have been recorded for this serial.</div>
            @endforelse
        </div>
    </section>
</div>
