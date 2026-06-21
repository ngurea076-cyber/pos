<div class="relative min-w-0">
    <label class="label">Product @if($action!=='stock_intake')<span class="normal-case text-slate-400">(available in stock)</span>@endif</label>
    <div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></i><input wire:model.live.debounce.200ms="productSearch" wire:focus="$set('showProductSuggestions',true)" class="input pl-9" placeholder="Start typing a product name" autocomplete="off"></div>
    @if($showProductSuggestions)
        <div class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
            @forelse($productSuggestions as $suggestion)
                <button type="button" wire:click="selectInventoryProduct({{ $suggestion->id }})" class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm hover:bg-brand-50"><span class="truncate">{{ $suggestion->name }}</span>@if($action!=='stock_intake')<small class="shrink-0 text-slate-500">{{ $suggestion->stock }} in stock</small>@endif</button>
            @empty<div class="p-3 text-center text-sm text-slate-500">No matching products.</div>@endforelse
        </div>
    @endif
    @error('productId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
