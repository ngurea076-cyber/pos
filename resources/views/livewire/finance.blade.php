<div class="space-y-6">
    <div><h1 class="text-2xl font-bold text-ink sm:text-3xl">Finance</h1><p class="text-sm text-slate-500">Private supplier costs and payment records.</p></div>

    <section class="card p-5 sm:p-6">
        <div class="mb-5"><h2 class="font-bold">Payment status check</h2><p class="text-xs text-slate-500">Enter an exact serial code to see paid, partial, or unpaid records.</p></div>
        <form wire:submit="searchPaymentStatus" class="flex max-w-2xl items-start gap-2">
            <div class="min-w-0 flex-1"><div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"></i><input wire:model="serialSearch" class="input pl-9" placeholder="Enter serial code" autofocus></div>@error('serialSearch')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            <button class="btn-primary shrink-0 gap-2"><i data-lucide="arrow-right" class="size-4"></i><span class="hidden sm:inline">Check status</span><span class="sm:hidden">Check</span></button>
        </form>
    </section>

    <div class="grid grid-cols-2 gap-2">
        @foreach([['price','Set buying price','badge-dollar-sign','Set costs and due dates by serial.'],['payment','Make payment','hand-coins','Record full or partial supplier payments.']] as [$key,$title,$icon,$caption])
            <button wire:click="chooseAction('{{ $key }}')" class="card flex items-center gap-2.5 p-3 text-left transition {{ $action===$key?'border-brand-500 bg-brand-50':'' }} hover:border-brand-500">
                <span class="grid size-9 shrink-0 place-items-center rounded-lg {{ $action===$key?'bg-brand-600 text-white':'bg-slate-100 text-ink' }}"><i data-lucide="{{ $icon }}" class="size-4"></i></span>
                <span class="min-w-0"><b class="block text-xs sm:text-sm">{{ $title }}</b><small class="hidden text-slate-500 lg:block">{{ $caption }}</small></span>
            </button>
        @endforeach
    </div>

    @if(in_array($action, ['price', 'payment'], true))
        <div wire:click.self="closeAction" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-slate-950/55 p-4">
        <section class="my-auto max-h-[calc(100vh-2rem)] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
            <div class="mb-5 flex items-start justify-between gap-3"><div><h2 class="font-bold">{{ $action==='price'?'Set buying price':'Make supplier payment' }}</h2><p class="text-xs text-slate-500">{{ $action==='price'?'Buying prices are visible to administrators only. All inventory states can be selected.':'Select priced serials and record a full or partial payment.' }}</p></div><button type="button" wire:click="closeAction" class="grid size-8 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close"><i data-lucide="x" class="size-5"></i></button></div>

            <form wire:submit="{{ $action==='price'?'saveBuyingPrice':'makePayment' }}" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label">Supplier</label><select wire:model.live="supplierId" class="input"><option value="">Select supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select>@error('supplierId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Product</label><select wire:model.live="productId" class="input" @disabled(!$supplierId)><option value="">Select product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select>@error('productId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3"><label class="label mb-0">Serial numbers</label>@if($serials->isNotEmpty())<label class="flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-600"><input type="checkbox" wire:model.live="selectAll" class="size-4 rounded border-slate-300 text-brand-600">Select all</label>@endif</div>
                    @if(!$productId)
                        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">Select a supplier and product.</div>
                    @elseif($serials->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">{{ $action==='payment'?'No priced serials with an outstanding balance.':'No serials found for this supplier and product.' }}</div>
                    @else
                        <div class="grid max-h-64 grid-cols-1 gap-1.5 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($serials as $serial)
                                @php($paid=(float)($serial->payment_allocations_sum_amount??0))
                                <label wire:key="finance-serial-{{ $serial->id }}" class="flex min-w-0 cursor-pointer items-center gap-2 rounded-lg bg-white px-2.5 py-2 shadow-sm hover:bg-brand-50">
                                    <input wire:model.live="serialIds" type="checkbox" value="{{ $serial->id }}" class="size-4 shrink-0 rounded border-slate-300 text-brand-600">
                                    <span class="min-w-0 flex-1">
                                        <code class="block truncate text-xs" title="{{ $serial->serial }}">{{ $serial->serial }}</code>
                                        <small class="block truncate text-slate-500">
                                            {{ $states[$serial->status] ?? $serial->status }}
                                            @if($serial->purchaseTerm)
                                                <span>· KES {{ number_format(max(0, (float) $serial->purchaseTerm->buying_price - $paid), 2) }} due</span>
                                            @endif
                                        </small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('serialIds')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('serialIds.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($action==='price')
                    <div class="grid gap-4 sm:grid-cols-2"><div><label class="label">Buying price per serial</label><div class="relative"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500">KES</span><input wire:model="buyingPrice" type="number" min="0.01" step="0.01" class="input pl-12" placeholder="0.00"></div>@error('buyingPrice')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="label">Due date</label><input wire:model="dueDate" type="date" class="input">@error('dueDate')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div>
                    <div><label class="label">Notes <span class="normal-case text-slate-400">(optional)</span></label><textarea wire:model="notes" class="input min-h-20" placeholder="Private finance notes"></textarea>@error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @else
                    <div><label class="label">Payment amount</label><div class="relative max-w-md"><span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500">KES</span><input wire:model="paymentAmount" type="number" min="0.01" step="0.01" class="input pl-12" placeholder="0.00"></div>@error('paymentAmount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Notes <span class="normal-case text-slate-400">(optional)</span></label><textarea wire:model="paymentNotes" class="input min-h-20" placeholder="Payment reference or notes"></textarea>@error('paymentNotes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @endif

                <div class="flex justify-end border-t border-slate-100 pt-5"><button class="btn-primary gap-2"><i data-lucide="{{ $action==='price'?'save':'hand-coins' }}" class="size-4"></i>{{ $action==='price'?'Save buying price':'Record payment' }}</button></div>
            </form>
        </section>
        </div>
    @endif
</div>
