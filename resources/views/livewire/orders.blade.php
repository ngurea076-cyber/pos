<div class="space-y-6">
    <div><h1 class="text-3xl font-bold text-ink">Orders</h1><p class="text-sm text-slate-500">All sales recorded by the till.</p></div>

    @if($order)
        <section class="card p-6">
            <div class="flex flex-wrap justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">{{ $order->order_number }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ $order->created_at->format('d M Y, H:i') }} · {{ $order->customer_name ?: 'Walk-in' }} ·
                        <span class="capitalize">{{ $order->customer_source ?? 'walkin' }}</span>
                        @if($order->is_invalid)<span class="ml-2 font-bold text-red-600">Deleted / invalid</span>@elseif($order->edit_count)<span class="ml-2 font-bold text-amber-600">Edited {{ $order->edit_count }}x</span>@endif
                    </p>
                </div>
                <b class="text-xl text-brand-700">KES {{ number_format($order->total, 2) }}</b>
            </div>
            @if($order->invalid_reason)<p class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700"><b>Invalid reason:</b> {{ $order->invalid_reason }}</p>@endif

            <div class="mt-5 divide-y">
                @foreach($order->items as $item)
                    <div class="flex justify-between gap-3 py-3 text-sm">
                        <span><b>{{ $item->name_snapshot }}</b><small class="block text-slate-500">Serial: {{ $item->serial_snapshot ?? '—' }} · Warranty: {{ $item->warranty_months ?? 0 }} months</small></span>
                        <b class="shrink-0">KES {{ number_format($item->line_total, 2) }}</b>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                @unless($order->is_invalid)
                    <button wire:click="openEdit" class="btn-muted"><i data-lucide="pencil" class="mr-2 size-4"></i>Edit</button>
                    @if(auth()->user()->isAdmin())<button wire:click="$set('showInvalidationForm',true)" class="btn-muted border-red-200 text-red-700"><i data-lucide="trash-2" class="mr-2 size-4"></i>Delete</button>@endif
                @endunless
                <a href="{{ route('orders.receipt', $order) }}" class="btn-primary" download><i data-lucide="arrow-down-to-line" class="mr-2 size-4"></i>Download PDF</a>
                <button type="button" onclick="shareReceipt(this)" data-url="{{ route('orders.receipt', $order) }}" data-filename="receipt-{{ $order->order_number }}.pdf" class="btn-muted"><i data-lucide="send" class="mr-2 size-4"></i>Share PDF to WhatsApp</button>
                <a wire:navigate href="{{ route('orders') }}" class="btn-muted"><i data-lucide="arrow-left" class="mr-2 size-4"></i>All orders</a>
            </div>
            <p class="mt-2 text-xs text-slate-400">On mobile, Share PDF opens your phone’s share menu—choose WhatsApp to send the receipt as an attachment.</p>
        </section>
    @else
        <section class="card overflow-hidden p-3 sm:p-5">
            <div class="mb-4 flex flex-wrap gap-3"><input wire:model.live.debounce.300ms="search" class="input min-w-52 flex-1" placeholder="Search order or customer"><select wire:model.live="period" class="input w-44"><option value="">All time</option><option value="1">Today</option><option value="3">Last 3 days</option><option value="7">Last 7 days</option><option value="15">Last 15 days</option><option value="30">Last 30 days</option></select></div>
            <div class="w-full overflow-hidden">
                <table class="w-full table-fixed text-xs sm:table-auto sm:text-sm">
                    <thead class="table-head"><tr><th class="w-[32%] py-3 sm:w-auto">Order ID</th><th class="hidden sm:table-cell">Date</th><th class="hidden sm:table-cell">Customer</th><th class="hidden sm:table-cell">Payment</th><th class="w-[30%] text-right sm:w-auto">Total</th><th class="w-[38%] text-right sm:w-auto">Actions</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse($orders as $item)
                            <tr class="{{ $item->is_invalid?'bg-red-50/60 opacity-75':'' }}">
                                <td class="min-w-0 py-3 pr-1 font-semibold"><span class="block truncate" title="{{ $item->order_number }}">{{ $item->order_number }}</span>@if($item->is_invalid)<small class="text-red-600">Deleted</small>@elseif($item->edit_count)<small class="text-amber-600">Edited</small>@endif</td>
                                <td class="hidden sm:table-cell">{{ $item->created_at->format('d M Y H:i') }}</td>
                                <td class="hidden sm:table-cell">{{ $item->customer_name ?: 'Walk-in' }}</td>
                                <td class="hidden uppercase sm:table-cell">{{ $item->payment_method }}</td>
                                <td class="whitespace-nowrap px-1 text-right font-bold"><span class="sm:hidden">{{ number_format($item->total) }}</span><span class="hidden sm:inline">KES {{ number_format($item->total, 2) }}</span></td>
                                <td class="pl-1">
                                    <div class="flex justify-end gap-1 sm:gap-1.5">
                                        <a wire:navigate href="{{ route('orders', ['order' => $item]) }}" class="grid size-7 shrink-0 place-items-center rounded-md border border-slate-200 text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 sm:size-8 sm:rounded-lg" title="View order" aria-label="View order {{ $item->order_number }}"><i data-lucide="eye" class="size-3.5 sm:size-4"></i></a>
                                        <a href="{{ route('orders.receipt', $item) }}" download class="grid size-7 shrink-0 place-items-center rounded-md border border-slate-200 text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 sm:size-8 sm:rounded-lg" title="Download receipt" aria-label="Download receipt {{ $item->order_number }}"><i data-lucide="arrow-down-to-line" class="size-3.5 sm:size-4"></i></a>
                                        <button type="button" onclick="shareReceipt(this)" data-url="{{ route('orders.receipt', $item) }}" data-filename="receipt-{{ $item->order_number }}.pdf" class="grid size-7 shrink-0 place-items-center rounded-md border border-slate-200 text-slate-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 disabled:opacity-50 sm:size-8 sm:rounded-lg" title="Share receipt" aria-label="Share receipt {{ $item->order_number }}"><i data-lucide="send" class="size-3.5 sm:size-4"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-12 text-center text-slate-500">No orders found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $orders->links() }}</div>
        </section>
    @endif

    @if($showEditForm && $order)
        <div class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-slate-950/50 p-4">
            <form wire:submit="saveCorrection" class="my-auto w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-xl font-bold">Correct order</h2><p class="text-sm text-slate-500">Corrections are audited and count against the edit limit.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2"><div><label class="label">Customer</label><input wire:model="customerName" class="input"></div><div><label class="label">Phone</label><input wire:model="customerPhone" class="input"></div><div><label class="label">Source</label><select wire:model="customerSource" class="input"><option value="walkin">Walk-in</option><option value="instagram">Instagram</option><option value="tiktok">TikTok</option><option value="returning">Returning</option><option value="reseller">Reseller</option></select></div><div><label class="label">Payment</label><select wire:model="paymentMethod" class="input"><option value="cash">Cash</option><option value="mpesa">M-Pesa</option><option value="card">Card</option><option value="bank">Bank</option></select></div></div>
                <div class="mt-4 space-y-2 rounded-xl border border-slate-200 p-3">@foreach($order->items as $line)<div class="grid gap-2 sm:grid-cols-[1fr_140px_120px]"><div class="min-w-0"><b class="block truncate text-sm">{{ $line->name_snapshot }}</b><small class="text-slate-500">{{ $line->serial_snapshot }}</small></div><input wire:model="editItems.{{ $line->id }}.unit_price" type="number" step="0.01" class="input" placeholder="Price"><input wire:model="editItems.{{ $line->id }}.warranty_months" type="number" class="input" placeholder="Warranty"></div>@endforeach</div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2"><div><label class="label">Discount</label><input wire:model="discount" type="number" step="0.01" class="input"></div><div><label class="label">Notes</label><textarea wire:model="notes" class="input min-h-20"></textarea></div></div>
                <div class="mt-4"><label class="label">Reason</label><textarea wire:model="correctionReason" class="input min-h-20" required></textarea>@error('correctionReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showEditForm',false)" class="btn-muted">Cancel</button><button class="btn-primary">Save correction</button></div>
            </form>
        </div>
    @endif
    @if($showInvalidationForm && $order)
        <div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4"><form wire:submit="invalidateOrder" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-xl font-bold text-red-700">Delete / invalidate order</h2><p class="text-sm text-slate-600">Serials still sold on this order will be released back into stock.</p><div class="mt-5"><label class="label">Reason</label><textarea wire:model="correctionReason" class="input min-h-24" required></textarea></div><div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showInvalidationForm',false)" class="btn-muted">Cancel</button><button class="btn-primary bg-red-600 hover:bg-red-700">Confirm deletion</button></div></form></div>
    @endif
</div>
