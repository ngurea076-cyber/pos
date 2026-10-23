<div class="space-y-6">
    <div class="flex items-end justify-between gap-3">
        <div><h1 class="text-3xl font-bold text-ink">Bills</h1><p class="text-sm text-slate-500">Set up recurring and one-time business obligations.</p></div>
        <button wire:click="create" class="btn-primary shrink-0"><i data-lucide="plus" class="mr-2 size-4"></i>Add bill</button>
    </div>

    <section class="card p-4 sm:p-5">
        <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="table-head"><tr><th class="py-3 text-left">Bill</th><th class="text-left">Frequency</th><th class="text-left">Due day</th><th class="text-right">Amount</th><th class="text-right">Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody class="divide-y">
                @forelse($bills as $bill)
                    <tr class="{{ $bill->is_active ? '' : 'opacity-60' }}">
                        <td class="py-3 pr-4"><b class="block">{{ $bill->name }}</b><small class="text-slate-500">{{ $bill->category }}</small>@if($bill->notes)<small class="block max-w-md truncate text-slate-500" title="{{ $bill->notes }}">{{ $bill->notes }}</small>@endif</td>
                        <td class="capitalize">{{ str_replace('_', ' ', $bill->frequency) }}</td>
                        <td>{{ $bill->due_day ? 'Day '.$bill->due_day : '—' }}</td>
                        <td class="whitespace-nowrap text-right font-bold">KES {{ number_format($bill->amount, 2) }}</td>
                        <td class="text-right"><span class="rounded-full px-2 py-1 text-xs font-medium {{ $bill->is_active ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500' }}">{{ $bill->is_active ? 'Active' : 'Paused' }}</span></td>
                        <td class="text-right"><div class="flex justify-end gap-1"><button wire:click="edit({{ $bill->id }})" aria-label="Edit {{ $bill->name }}" class="grid size-8 place-items-center rounded-lg border border-slate-200 text-brand-700"><i data-lucide="pencil" class="size-4"></i></button><button wire:click="toggleActive({{ $bill->id }})" class="rounded-lg border border-slate-200 px-2 text-xs font-medium">{{ $bill->is_active ? 'Pause' : 'Activate' }}</button></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-14 text-center text-slate-500">No bills yet. Add rent, salaries, utilities, and other obligations.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <p class="mt-4 text-xs text-slate-500">This is a planning list. To record a payment, add it under Expenses.</p>
    </section>

    @if($showForm)
        <div wire:click.self="closeForm" class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-slate-950/50 p-4">
            <form wire:submit="save" class="my-auto w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-start justify-between gap-3"><div><h2 class="text-xl font-bold">{{ $editing ? 'Edit bill' : 'Add bill' }}</h2><p class="text-sm text-slate-500">Set the expected amount and schedule.</p></div><button type="button" wire:click="closeForm" class="text-slate-400"><i data-lucide="x" class="size-5"></i></button></div>
                <div class="space-y-4">
                    <div><label class="label">Bill name</label><input wire:model="name" class="input" placeholder="e.g. Shop rent">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Category</label><input wire:model="category" class="input" placeholder="e.g. Rent, Salary, Utilities">@error('category')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="label">Expected amount (KES)</label><input wire:model="amount" class="input" type="number" min="0.01" step="0.01" placeholder="0.00">@error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-3"><div><label class="label">Frequency</label><select wire:model="frequency" class="input"><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="yearly">Yearly</option><option value="one_time">One-time</option></select>@error('frequency')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="label">Due day <span class="normal-case text-slate-400">(optional)</span></label><input wire:model="dueDay" class="input" type="number" min="1" max="31" placeholder="1–31">@error('dueDay')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div>
                    <div><label class="label">Notes <span class="normal-case text-slate-400">(optional)</span></label><textarea wire:model="notes" class="input min-h-20" placeholder="Additional details"></textarea>@error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                <div class="mt-7 flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" wire:click="closeForm" class="btn-muted">Cancel</button><button class="btn-primary">{{ $editing ? 'Save changes' : 'Save bill' }}</button></div>
            </form>
        </div>
    @endif
</div>
