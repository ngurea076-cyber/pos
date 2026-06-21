<div class="space-y-6">
    <div class="flex items-end justify-between gap-3">
        <div><h1 class="text-3xl font-bold text-ink">Expenses</h1><p class="text-sm text-slate-500">Record and review business expenses.</p></div>
        <button wire:click="create" class="btn-primary shrink-0"><i data-lucide="plus" class="mr-2 size-4"></i>Add expense</button>
    </div>

    <section class="card p-4 sm:p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head"><tr><th class="py-3">Expense</th><th>Category</th><th>Date</th><th>Recorded by</th><th class="text-right">Amount</th><th class="text-right">Actions</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($expenses as $item)
                        <tr class="{{ $item->is_invalid?'bg-red-50/60 opacity-75':'' }}">
                            <td class="min-w-52 py-3 pr-4"><b class="block">{{ $item->expense }}</b>@if($item->is_invalid)<span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">Deleted / invalid</span>@elseif($item->edit_count)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">Edited {{ $item->edit_count }}x</span>@endif @if($item->notes)<small class="block max-w-md truncate text-slate-500" title="{{ $item->notes }}">{{ $item->notes }}</small>@endif @if($item->invalid_reason)<small class="block text-red-600">{{ $item->invalid_reason }}</small>@endif</td>
                            <td><span class="whitespace-nowrap rounded-full bg-slate-100 px-2 py-1 text-xs font-medium">{{ ucfirst($item->category) }}</span></td>
                            <td class="whitespace-nowrap">{{ $item->created_at->format('d M Y, H:i') }}</td>
                            <td>{{ $item->user?->name ?? '—' }}</td>
                            <td class="whitespace-nowrap text-right font-bold">KES {{ number_format($item->amount, 2) }}</td>
                            <td class="text-right"><div class="flex justify-end gap-1">@unless($item->is_invalid)<button wire:click="edit({{ $item->id }})" class="grid size-8 place-items-center rounded-lg border border-slate-200 text-brand-700"><i data-lucide="pencil" class="size-4"></i></button>@if(auth()->user()->isAdmin())<button wire:click="confirmInvalidate({{ $item->id }})" class="grid size-8 place-items-center rounded-lg border border-red-200 text-red-700"><i data-lucide="trash-2" class="size-4"></i></button>@endif @endunless</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-14 text-center text-slate-500">No expenses recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $expenses->links() }}</div>
    </section>

    @if($showForm)
        <div wire:click.self="closeForm" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-slate-950/50 p-4">
            <form wire:submit="save" class="my-auto w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-start justify-between gap-3"><div><h2 class="text-xl font-bold">{{ $editing ? 'Correct expense' : 'Add expense' }}</h2><p class="text-sm text-slate-500">{{ $editing ? 'Corrections are audited and count against the edit limit.' : 'Record a business expense.' }}</p></div><button type="button" wire:click="closeForm" class="text-slate-400"><i data-lucide="x" class="size-5"></i></button></div>
                <div class="space-y-4">
                    <div><label class="label">Expense</label><input wire:model="expense" class="input" placeholder="Expense name" autofocus>@error('expense')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Category</label><select wire:model="category" class="input"><option value="">Select category</option><option value="food">Food</option><option value="utilities">Utilities</option><option value="fare">Fare</option></select>@error('category')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Amount (KES)</label><input wire:model="amount" class="input" type="number" min="0.01" step="0.01" placeholder="0.00">@error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Notes <span class="normal-case text-slate-400">(optional)</span></label><textarea wire:model="notes" class="input min-h-24" placeholder="Additional details"></textarea>@error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    @if($editing)<div><label class="label">Reason for correction</label><textarea wire:model="correctionReason" class="input min-h-20" required></textarea>@error('correctionReason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>@endif
                </div>
                <div class="mt-7 flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" wire:click="closeForm" class="btn-muted">Cancel</button><button class="btn-primary">{{ $editing ? 'Save correction' : 'Save expense' }}</button></div>
            </form>
        </div>
    @endif
    @if($showInvalidationForm)<div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4"><form wire:submit="invalidate" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-xl font-bold text-red-700">Delete / invalidate expense</h2><p class="text-sm text-slate-600">It stays visible as deleted and is excluded from financial totals.</p><div class="mt-5"><label class="label">Reason</label><textarea wire:model="correctionReason" class="input min-h-24" required></textarea>@error('correctionReason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div><div class="mt-6 flex justify-end gap-2"><button type="button" wire:click="$set('showInvalidationForm',false)" class="btn-muted">Cancel</button><button class="btn-primary bg-red-600 hover:bg-red-700">Confirm deletion</button></div></form></div>@endif
</div>
