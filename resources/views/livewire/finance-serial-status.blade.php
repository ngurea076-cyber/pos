<div class="space-y-6">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0"><a wire:navigate href="{{ route('finance', ['action'=>'status']) }}" class="mb-2 inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline"><i data-lucide="arrow-left" class="size-3.5"></i>Finance</a><h1 class="truncate text-2xl font-bold text-ink sm:text-3xl">Payment status</h1><p class="truncate font-mono text-sm text-slate-500">{{ $serial->serial }}</p></div>
        @php($statusStyle=match($status){'paid'=>'bg-green-50 text-green-700','partial'=>'bg-amber-50 text-amber-700','unpaid'=>'bg-red-50 text-red-700',default=>'bg-slate-100 text-slate-600'})
        <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-bold {{ $statusStyle }}">{{ match($status){'paid'=>'Paid','partial'=>'Partially paid','unpaid'=>'Unpaid',default=>'Price not set'} }}</span>
    </div>

    <section class="card p-5 sm:p-6">
        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3 lg:grid-cols-6">
            <div><small class="block text-slate-500">Product</small><b>{{ $serial->product->name }}</b></div>
            <div><small class="block text-slate-500">Supplier</small><b>{{ $serial->purchaseTerm?->supplier?->name ?? $serial->supplier?->name ?? '—' }}</b></div>
            <div><small class="block text-slate-500">Inventory state</small><b>{{ ['in_stock'=>'In stock','with_reseller'=>'Take-out','sold'=>'Sold','returned_to_supplier'=>'Returned to supplier'][$serial->status]??$serial->status }}</b></div>
            <div><small class="block text-slate-500">Buying price</small><b>{{ $serial->purchaseTerm?'KES '.number_format($price,2):'Not set' }}</b></div>
            <div><small class="block text-slate-500">Paid</small><b class="text-green-700">KES {{ number_format($paid,2) }}</b></div>
            <div><small class="block text-slate-500">Balance</small><b class="text-brand-700">KES {{ number_format(max(0,$price-$paid),2) }}</b></div>
        </div>
        @if($serial->purchaseTerm)
            <div class="mt-5 grid gap-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-3"><div><small class="block text-slate-500">Due date</small><b>{{ $serial->purchaseTerm->due_date?->format('d M Y') ?? 'Not set' }}</b></div><div><small class="block text-slate-500">Price set by</small><b>{{ $serial->purchaseTerm->setter?->name ?? '—' }}</b></div><div><small class="block text-slate-500">Notes</small><b>{{ $serial->purchaseTerm->notes ?: '—' }}</b></div></div>
        @endif
    </section>

    <section class="card overflow-hidden">
        <div class="border-b border-slate-100 p-5"><h2 class="font-bold">Payment records</h2><p class="text-xs text-slate-500">Every full or partial payment allocated to this serial.</p></div>
        <div class="divide-y">
            @forelse($serial->paymentAllocations as $allocation)
                <article class="grid gap-3 p-4 text-sm sm:grid-cols-[1fr_auto_auto_auto] sm:items-center sm:p-5 {{ $allocation->payment->is_invalid?'bg-red-50/70 opacity-75':'' }}">
                    <div><b class="block">KES {{ number_format($allocation->amount,2) }}</b><small class="text-slate-500">Recorded by {{ $allocation->payment->recorder?->name ?? '—' }}@if($allocation->payment->notes) · {{ $allocation->payment->notes }}@endif</small>@if($allocation->payment->is_invalid)<small class="block font-semibold text-red-600">Deleted / invalid: {{ $allocation->payment->invalid_reason }}</small>@elseif($allocation->payment->edit_count)<small class="block font-semibold text-amber-600">Edited {{ $allocation->payment->edit_count }}x</small>@endif</div>
                    <div class="text-xs text-slate-500 sm:text-right"><span class="block">{{ $allocation->payment->paid_at->format('d M Y') }}</span><span>{{ $allocation->payment->paid_at->format('H:i') }}</span></div>
                    <span class="w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">Payment #{{ $allocation->payment->id }}</span>
                    @unless($allocation->payment->is_invalid)<div class="flex gap-1 sm:justify-end"><button wire:click="editPayment({{ $allocation->id }})" class="grid size-8 place-items-center rounded-lg border border-slate-200 text-brand-700"><i data-lucide="pencil" class="size-4"></i></button><button wire:click="confirmInvalidatePayment({{ $allocation->payment->id }})" class="grid size-8 place-items-center rounded-lg border border-red-200 text-red-700"><i data-lucide="trash-2" class="size-4"></i></button></div>@endunless
                </article>
            @empty
                <div class="py-12 text-center"><i data-lucide="receipt" class="mx-auto size-8 text-slate-300"></i><p class="mt-2 text-sm text-slate-500">No payments have been recorded for this serial.</p></div>
            @endforelse
        </div>
    </section>

    @if($showPaymentEdit)<div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4"><form wire:submit="savePaymentCorrection" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-xl font-bold">Correct payment</h2><div class="mt-5 grid gap-4 sm:grid-cols-2"><div><label class="label">Amount</label><input wire:model="paymentAmount" type="number" step="0.01" class="input"></div><div><label class="label">Date</label><input wire:model="paymentDate" type="datetime-local" class="input"></div></div><div class="mt-4"><label class="label">Notes</label><textarea wire:model="paymentNotes" class="input min-h-20"></textarea></div><div class="mt-4"><label class="label">Reason</label><textarea wire:model="correctionReason" class="input min-h-20" required></textarea>@error('correctionReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div><div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showPaymentEdit',false)" class="btn-muted">Cancel</button><button class="btn-primary">Save correction</button></div></form></div>@endif
    @if($showPaymentInvalidation)<div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4"><form wire:submit="invalidatePayment" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-xl font-bold text-red-700">Delete / invalidate payment</h2><p class="text-sm text-slate-600">The payment remains visible but no longer counts as paid.</p><div class="mt-5"><label class="label">Reason</label><textarea wire:model="correctionReason" class="input min-h-24" required></textarea></div><div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showPaymentInvalidation',false)" class="btn-muted">Cancel</button><button class="btn-primary bg-red-600 hover:bg-red-700">Confirm deletion</button></div></form></div>@endif
</div>
