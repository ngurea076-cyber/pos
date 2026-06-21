<div class="mx-auto max-w-6xl space-y-6">
    <div><h1 class="text-2xl font-bold text-ink sm:text-3xl">New sale</h1><p class="text-sm text-slate-500">Complete the three steps to record a sale.</p></div>

    <ol class="grid grid-cols-3 gap-2">
        @foreach([[1,'Customer'],[2,'Products'],[3,'Review']] as [$number,$label])
            <li class="flex items-center gap-2 rounded-xl border p-3 {{ $step === $number ? 'border-brand-500 bg-brand-50 text-brand-700' : ($step > $number ? 'border-brand-100 bg-white text-brand-700' : 'border-slate-200 bg-white text-slate-400') }}">
                <span class="grid size-7 shrink-0 place-items-center rounded-full {{ $step >= $number ? 'bg-brand-600 text-white' : 'bg-slate-100' }} text-xs font-bold">{{ $step > $number ? '✓' : $number }}</span>
                <span class="hidden text-sm font-semibold sm:block">{{ $label }}</span>
            </li>
        @endforeach
    </ol>

    @if($step === 1)
        <section class="card mx-auto max-w-2xl p-5 sm:p-7">
            <h2 class="text-lg font-bold">Customer details</h2><p class="mb-6 text-sm text-slate-500">Who is making this purchase?</p>
            <form wire:submit="customerNext" class="space-y-4">
                <div><label class="label">Customer name</label><input wire:model="customerName" class="input" placeholder="Full name" autofocus>@error('customerName')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Phone number <span class="normal-case text-slate-400">(optional)</span></label><input wire:model="customerPhone" class="input" type="tel" placeholder="e.g. 0712 345 678">@error('customerPhone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="label">Customer source</label><select wire:model="customerSource" class="input"><option value="walkin">Walk-in</option><option value="instagram">Instagram</option><option value="tiktok">TikTok</option><option value="returning">Returning customer</option><option value="reseller">Reseller</option></select></div>
                <div class="flex justify-end border-t border-slate-100 pt-5"><button class="btn-primary">Next: Select products <i data-lucide="arrow-right" class="ml-2 size-4"></i></button></div>
            </form>
        </section>
    @elseif($step === 2)
        <div class="grid gap-4 lg:grid-cols-[1fr_360px]">
            <section class="card self-start p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <button wire:click="$set('step',1)" class="btn-muted shrink-0 px-3"><i data-lucide="arrow-left" class="size-4"></i></button>
                    <div class="min-w-0 flex-1">
                        <label class="label">Search product</label>
                        <input wire:model.live.debounce.250ms="search" class="input" placeholder="Start typing a product name" autocomplete="off" autofocus>
                        @if(filled($search))
                            <div class="mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                @forelse($products as $product)
                                    <button type="button" wire:click="selectProduct({{ $product->id }})" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-3 py-2.5 text-left last:border-0 hover:bg-brand-50">
                                        <span class="min-w-0 truncate text-sm font-semibold">{{ $product->name }}</span>
                                        <span class="shrink-0 text-xs text-slate-500">{{ $product->availableSerials->count() }} available</span>
                                    </button>
                                @empty
                                    <p class="px-3 py-4 text-center text-sm text-slate-500">No in-stock product found.</p>
                                @endforelse
                                <button type="button" wire:click="openCustomProduct" class="flex w-full items-center gap-2 border-t border-slate-200 bg-slate-50 px-3 py-3 text-left text-sm font-semibold text-brand-700 hover:bg-brand-50"><i data-lucide="plus" class="size-4"></i>Add “{{ trim($search) }}” as a custom product</button>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <aside class="card self-start p-5 lg:sticky lg:top-6">
                <div class="flex items-center justify-between"><h2 class="font-bold">Selected products</h2><span class="text-xs text-slate-500">{{ count($cart) }} unit{{ count($cart) === 1 ? '' : 's' }}</span></div>
                <div class="my-4 max-h-72 space-y-2 overflow-y-auto">
                    @forelse($cart as $cartKey => $item)
                        <div class="rounded-xl bg-slate-50 p-3"><div class="flex gap-2"><div class="min-w-0 flex-1"><b class="block truncate text-sm">{{ $item['name'] }}</b><span class="block truncate text-xs text-slate-500">Serial: {{ $item['serial'] }}</span><span class="text-xs text-slate-500">{{ $item['warranty_months'] }} month warranty</span>@if($item['is_custom'] ?? false)<span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Custom</span>@endif</div><button wire:click="remove(@js((string) $cartKey))" class="self-start text-xl text-red-500"><i data-lucide="x" class="size-5"></i></button></div><div class="mt-2 text-right text-sm font-bold">KES {{ number_format($item['price'],2) }}</div></div>
                    @empty<p class="py-8 text-center text-sm text-slate-500">No products selected.</p>@endforelse
                </div>
                <div class="space-y-3 border-t pt-4"><div class="flex justify-between"><span>Subtotal</span><b>KES {{ number_format($subtotal,2) }}</b></div><div><label class="label">Discount</label><input wire:model="discount" class="input" type="number" min="0" step=".01"></div><div><label class="label">Payment method</label><select wire:model="paymentMethod" class="input"><option value="cash">Cash</option><option value="mpesa">M-Pesa</option><option value="card">Card</option><option value="bank">Bank transfer</option></select></div>@error('discount')<p class="text-xs text-red-600">{{ $message }}</p>@enderror<button wire:click="review" class="btn-primary w-full" @disabled(empty($cart))>Review order <i data-lucide="arrow-right" class="ml-2 size-4"></i></button></div>
            </aside>
        </div>

        @if($selectedProduct)
            <div wire:click.self="cancelSelection" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4">
                <form wire:submit="addSelectedProduct" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="mb-5 flex justify-between gap-3"><div><h2 class="text-xl font-bold">Select product unit</h2><p class="mt-1 text-sm text-slate-500">{{ $selectedProduct->name }}</p></div><button type="button" wire:click="cancelSelection" class="text-2xl text-slate-400"><i data-lucide="x" class="size-5"></i></button></div>
                    <div class="space-y-4">
                        @if($resellerSaleSerial)
                            <div><label class="label">Reseller</label><input class="input bg-slate-50" value="{{ $resellerSaleSerial->reseller?->name }}" disabled></div>
                            <div><label class="label">Serial code</label><input class="input bg-slate-50 font-mono" value="{{ $resellerSaleSerial->serial }}" disabled></div>
                        @else
                            <div><label class="label">Serial code</label><select wire:model="selectedSerialId" class="input"><option value="">Choose an available serial…</option>@foreach($selectedProduct->availableSerials as $serial)@unless(isset($cart[$serial->id]))<option value="{{ $serial->id }}">{{ $serial->serial }}</option>@endunless @endforeach</select>@error('selectedSerialId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        @endif
                        <div><label class="label">Warranty in months</label><input wire:model="warrantyMonths" class="input" type="number" min="0" max="120" placeholder="0">@error('warrantyMonths')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="label">Sale price (KES)</label><input wire:model="price" class="input" type="number" min="0.01" step=".01" placeholder="0.00">@error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="mt-7 flex justify-end gap-3 border-t border-slate-100 pt-5"><button type="button" wire:click="cancelSelection" class="btn-muted">Cancel</button><button class="btn-primary">Add to order</button></div>
                </form>
            </div>
        @endif

        @if($customProductOpen)
            <div wire:click.self="closeCustomProduct" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-slate-950/50 p-4">
                <form wire:submit="addCustomProduct" class="my-auto w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div><h2 class="text-xl font-bold">Add {{ $customProductName }}</h2></div>
                        <button type="button" wire:click="closeCustomProduct" class="text-slate-400"><i data-lucide="x" class="size-5"></i></button>
                    </div>
                    <div class="space-y-4">
                        <div><label class="label">Supplier</label><select wire:model="customSupplierId" class="input"><option value="">Select supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select>@error('customSupplierId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="label">Serial code</label><input wire:model="customSerial" class="input" placeholder="Serial code">@error('customSerial')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="label">Warranty (months)</label><input wire:model="customWarrantyMonths" class="input" type="number" min="0" max="120" placeholder="0">@error('customWarrantyMonths')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                            <div><label class="label">Sale price (KES)</label><input wire:model="customPrice" class="input" type="number" min="0.01" step=".01" placeholder="0.00">@error('customPrice')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        </div>
                    </div>
                    <div class="mt-7 flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" wire:click="closeCustomProduct" class="btn-muted">Cancel</button><button class="btn-primary">Add to order</button></div>
                </form>
            </div>
        @endif
    @else
        <section class="card mx-auto max-w-3xl p-5 sm:p-7">
            <div class="flex items-start justify-between gap-3"><div><h2 class="text-xl font-bold">Review order</h2><p class="text-sm text-slate-500">Confirm everything before recording the sale.</p></div><button wire:click="$set('step',2)" class="btn-muted"><i data-lucide="arrow-left" class="mr-2 size-4"></i>Edit</button></div>
            <div class="mt-6 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3"><div><span class="block text-xs text-slate-500">Customer</span><b>{{ $customerName }}</b></div><div><span class="block text-xs text-slate-500">Phone</span><b>{{ $customerPhone ?: 'Not provided' }}</b></div><div><span class="block text-xs text-slate-500">Source</span><b class="capitalize">{{ $customerSource === 'walkin' ? 'Walk-in' : $customerSource }}</b></div></div>
            <div class="mt-6 divide-y rounded-xl border border-slate-200">@foreach($cart as $item)<div class="flex items-start justify-between gap-3 p-4"><div class="min-w-0"><b class="block truncate">{{ $item['name'] }}</b><p class="text-xs text-slate-500">Serial: {{ $item['serial'] }} · Warranty: {{ $item['warranty_months'] }} months</p></div><b class="shrink-0">KES {{ number_format($item['price'],2) }}</b></div>@endforeach</div>
            <div class="ml-auto mt-5 max-w-sm space-y-2 text-sm"><div class="flex justify-between"><span>Subtotal</span><b>KES {{ number_format($subtotal,2) }}</b></div><div class="flex justify-between"><span>Discount</span><b>− KES {{ number_format($discount,2) }}</b></div><div class="flex justify-between border-t pt-3 text-lg"><b>Total</b><b class="text-brand-700">KES {{ number_format(max(0,$subtotal-$discount),2) }}</b></div><div class="flex justify-between"><span>Payment</span><b class="uppercase">{{ $paymentMethod }}</b></div></div>
            <div class="mt-6"><label class="label">Order notes (optional)</label><textarea wire:model="notes" class="input min-h-20" placeholder="Any additional information"></textarea></div>
            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5"><button wire:click="$set('step',2)" class="btn-muted">Back</button><button wire:click="checkout" wire:loading.attr="disabled" class="btn-primary">Confirm sale</button></div>
        </section>
    @endif
</div>
