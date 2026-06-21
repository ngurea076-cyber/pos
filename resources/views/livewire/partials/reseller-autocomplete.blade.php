<div class="relative min-w-0">
    <label class="label">Reseller</label>
    <div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></i><input wire:model.live.debounce.200ms="resellerSearch" wire:focus="$set('showResellerSuggestions',true)" class="input pl-9" placeholder="Start typing reseller name" autocomplete="off"></div>
    @if($showResellerSuggestions)<div class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">@forelse($resellerSuggestions as $suggestion)<button type="button" wire:click="selectInventoryReseller({{ $suggestion->id }})" class="block w-full truncate rounded-lg px-3 py-2 text-left text-sm hover:bg-brand-50">{{ $suggestion->name }}</button>@empty<div class="p-3 text-center text-sm text-slate-500">No matching resellers.</div>@endforelse</div>@endif
    @error('resellerId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
