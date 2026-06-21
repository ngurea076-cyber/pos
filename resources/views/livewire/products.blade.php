<div class="space-y-6">
    <div class="flex flex-nowrap items-center justify-between gap-3">
        <div class="min-w-0 flex-1"><h1 class="text-2xl font-bold text-ink sm:text-3xl">Products</h1><p class="truncate text-xs text-slate-500 sm:text-sm">Your complete product catalogue.</p></div>
        <button wire:click="create" class="btn-primary shrink-0 gap-2 px-3 sm:px-4"><i data-lucide="plus" class="size-4"></i>Add product</button>
    </div>

    <section class="card p-5">
        <input wire:model.live.debounce.300ms="search" class="input mb-4 max-w-md" placeholder="Search product name">
        <div class="overflow-x-auto">
            <table class="w-full table-fixed text-sm">
                <thead class="table-head"><tr><th class="py-3">Product</th><th class="w-16 text-right sm:w-24">Action</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($products as $p)
                        <tr>
                            <td class="min-w-0 py-3 pr-3 font-semibold"><span class="block truncate" title="{{ $p->name }}">{{ $p->name }}</span></td>
                            <td class="text-right">
                                <div class="flex justify-end">
                                    <button wire:click="edit({{ $p->id }})" class="inline-flex size-8 items-center justify-center rounded-lg border border-slate-200 text-brand-600 transition hover:border-brand-200 hover:bg-brand-50 sm:w-auto sm:gap-1.5 sm:px-3" title="Edit {{ $p->name }}" aria-label="Edit {{ $p->name }}"><i data-lucide="pencil" class="size-3.5"></i><span class="hidden sm:inline">Edit</span></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-12 text-center text-slate-500">No products found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </section>

    @if($showForm)
        <div wire:click.self="cancelEdit" class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" role="dialog" aria-modal="true">
            <form wire:submit="save" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div><h2 class="text-xl font-bold text-ink">{{ $editing ? 'Edit product' : 'Add product' }}</h2><p class="mt-1 text-sm text-slate-500">{{ $editing ? 'Update the product name.' : 'Enter the product name.' }}</p></div>
                    <button type="button" wire:click="cancelEdit" class="grid size-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close"><i data-lucide="x" class="size-5"></i></button>
                </div>
                <div><label class="label" for="product-name">Product name</label><input id="product-name" wire:model="name" class="input" placeholder="Product name" required autofocus>@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-5"><button type="button" wire:click="cancelEdit" class="btn-muted">Cancel</button><button class="btn-primary">{{ $editing ? 'Save changes' : 'Add product' }}</button></div>
            </form>
        </div>
    @endif
</div>
