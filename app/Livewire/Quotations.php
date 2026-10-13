<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Quotations extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $formOpen = false;
    public ?int $editingId = null;
    public string $quotationNumber = '';
    public string $billTo = '';
    public string $issuedAt = '';
    public bool $includeVat = false;
    public string $discount = '0';
    public string $terms = '';
    public string $productSearch = '';
    public ?int $productSearchIndex = null;
    public array $items = [];

    public function mount(): void
    {
        $this->issuedAt = now()->toDateString();
        $this->terms = config('quotation.terms') ?? '';
        $this->quotationNumber = $this->newNumber();
        if (request()->boolean('create')) $this->formOpen = true;
    }

    public function create(): void
    {
        $this->resetForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $quotation = Quotation::with(['items', 'attendant'])->findOrFail($id);
        abort_if($quotation->status === 'converted', 422, 'Converted quotations cannot be edited.');
        $this->editingId = $quotation->id;
        $this->quotationNumber = $quotation->quotation_number;
        $this->billTo = $quotation->bill_to;
        $this->issuedAt = $quotation->issued_at->toDateString();
        $this->includeVat = $quotation->vat_percent > 0;
        $this->discount = (string) $quotation->discount;
        $this->terms = $quotation->terms ?? '';
        $this->items = $quotation->items->map(fn ($item) => ['product_id' => $item->product_id, 'name' => $item->name, 'unit_price' => (string) $item->unit_price, 'quantity' => (string) $item->quantity])->all();
        $this->formOpen = true;
    }

    public function duplicate(int $id): void
    {
        $quotation = Quotation::with('items')->findOrFail($id);
        $this->create();
        $this->billTo = $quotation->bill_to;
        $this->includeVat = $quotation->vat_percent > 0;
        $this->discount = (string) $quotation->discount;
        $this->terms = $quotation->terms ?? '';
        $this->items = $quotation->items->map(fn ($item) => ['product_id' => $item->product_id, 'name' => $item->name, 'unit_price' => (string) $item->unit_price, 'quantity' => (string) $item->quantity])->all();
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'name' => '', 'unit_price' => '0', 'quantity' => '1'];
        $this->productSearchIndex = null;
    }

    public function selectProduct(int $index, int $productId): void
    {
        $product = Product::findOrFail($productId);
        $this->items[$index] = ['product_id' => $product->id, 'name' => $product->name, 'unit_price' => (string) $product->selling_price, 'quantity' => $this->items[$index]['quantity'] ?? '1'];
        $this->productSearch = '';
        $this->productSearchIndex = null;
    }

    public function updatedItems($value, string $key): void
    {
        if (str_ends_with($key, '.name')) {
            $this->productSearchIndex = (int) explode('.', $key)[0];
            $this->productSearch = (string) $value;
            $this->items[$this->productSearchIndex]['product_id'] = null;
        }
    }

    public function useCustomProduct(int $index): void
    {
        $this->items[$index]['product_id'] = null;
        $this->productSearch = '';
        $this->productSearchIndex = null;
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $data = $this->validate([
            'billTo' => ['required', 'string', 'max:255'],
            'issuedAt' => ['required', 'date'],
            'includeVat' => ['boolean'],
            'discount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $lines = collect($data['items'])->map(fn ($item) => [...$item, 'quantity' => (int) $item['quantity'], 'unit_price' => round((float) $item['unit_price'], 2), 'amount' => round((float) $item['unit_price'] * (int) $item['quantity'], 2)]);
        $subtotal = round($lines->sum('amount'), 2);
        $discount = round((float) $data['discount'], 2);
        if ($discount > $subtotal) {
            $this->addError('discount', 'Discount cannot be greater than the subtotal.');
            return;
        }

        $quotationId = DB::transaction(function () use ($data, $lines, $subtotal, $discount) {
            $vatPercent = $data['includeVat'] ? 16 : 0;
            $vat = round(($subtotal - $discount) * $vatPercent / 100, 2);
            $attributes = ['quotation_number' => $this->quotationNumber, 'bill_to' => trim($data['billTo']), 'issued_at' => $data['issuedAt'], 'expires_at' => \Illuminate\Support\Carbon::parse($data['issuedAt'])->addDays(5)->toDateString(), 'served_by' => auth()->id(), 'served_by_name' => config('quotation.served_by'), 'subtotal' => $subtotal, 'discount' => $discount, 'vat_percent' => $vatPercent, 'vat_amount' => $vat, 'total' => $subtotal - $discount + $vat, 'terms' => filled($data['terms']) ? $data['terms'] : null, 'status' => 'draft'];
            $quotation = $this->editingId ? Quotation::lockForUpdate()->findOrFail($this->editingId) : new Quotation();
            if ($this->editingId) $quotation->items()->delete();
            $quotation->fill($attributes)->save();
            foreach ($lines as $line) $quotation->items()->create(['product_id' => $line['product_id'] ?: null, 'name' => trim($line['name']), 'unit_price' => $line['unit_price'], 'quantity' => $line['quantity'], 'amount' => $line['amount']]);
        });

        $this->formOpen = false;
        $this->resetForm();
        session()->flash('status', 'Quotation saved.');
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(in_array($status, ['sent', 'accepted', 'rejected'], true), 422);
        $quotation = Quotation::findOrFail($id);
        abort_if(in_array($quotation->status, ['converted', 'expired'], true), 422, 'This quotation can no longer be updated.');
        $quotation->update(['status' => $status]);
    }

    public function send(int $id): void
    {
        $this->setStatus($id, 'sent');
    }

    public function delete(int $id): void
    {
        $quotation = Quotation::findOrFail($id);
        abort_if($quotation->status === 'converted', 422, 'Converted quotations cannot be deleted.');
        $quotation->delete();
    }

    public function convert(int $id): void
    {
        $quotation = Quotation::with('items')->findOrFail($id);
        abort_if(in_array($quotation->status, ['expired', 'rejected', 'converted'], true), 422, 'This quotation cannot be converted.');

        $order = DB::transaction(function () use ($quotation) {
            $locked = Quotation::with('items')->lockForUpdate()->findOrFail($quotation->id);
            abort_if($locked->expires_at->isBefore(today()) || $locked->status === 'converted', 409, 'This quotation has expired or was already converted.');
            $quantities = $locked->items->whereNotNull('product_id')->groupBy('product_id')->map->sum('quantity');
            abort_if($locked->items->contains(fn ($item) => ! $item->product_id), 422, 'Link every quotation line to an inventory product before converting it to a sale.');
            $serialsByProduct = collect();
            foreach ($quantities as $productId => $quantity) {
                $serials = ProductSerial::where('product_id', $productId)->where('status', 'in_stock')->orderBy('id')->limit($quantity)->lockForUpdate()->get();
                abort_unless($serials->count() === $quantity, 409, 'There is not enough serialized stock to convert this quotation.');
                $serialsByProduct->put($productId, $serials);
            }
            $order = Order::create(['customer_name' => $locked->bill_to, 'customer_source' => 'walkin', 'subtotal' => $locked->subtotal, 'discount' => $locked->discount, 'tax' => $locked->vat_amount, 'total' => $locked->total, 'payment_method' => 'cash', 'notes' => 'Converted from quotation '.$locked->quotation_number, 'attendant_id' => auth()->id()]);
            $serialOffsets = [];
            foreach ($locked->items as $line) {
                $offset = $serialOffsets[$line->product_id] ?? 0;
                foreach ($serialsByProduct[$line->product_id]->slice($offset, $line->quantity) as $serial) {
                    $order->items()->create(['product_id' => $line->product_id, 'product_serial_id' => $serial->id, 'name_snapshot' => $line->name, 'serial_snapshot' => $serial->serial, 'warranty_months' => 0, 'quantity' => 1, 'unit_price' => $line->unit_price, 'line_total' => $line->unit_price]);
                    InventoryRecord::create(['type'=>'sale','product_id'=>$line->product_id,'product_serial_id'=>$serial->id,'supplier_id'=>$serial->supplier_id,'quantity'=>-1,'reference'=>'SALE-'.$order->id.'-'.$serial->id,'notes'=>'Quotation '.$locked->quotation_number,'created_by'=>auth()->id(),'happened_at'=>now()]);
                    $serial->update(['status' => 'sold', 'order_id' => $order->id, 'sold_at' => now()]);
                    Product::whereKey($line->product_id)->decrement('stock');
                }
                $serialOffsets[$line->product_id] = $offset + $line->quantity;
            }
            $locked->update(['status' => 'converted', 'order_id' => $order->id]);
            return $order;
        });

        $this->redirectRoute('orders', ['order' => $order->id]);
    }

    public function totals(): array
    {
        $subtotal = round(collect($this->items)->sum(fn ($item) => (float) ($item['unit_price'] ?? 0) * (int) ($item['quantity'] ?? 0)), 2);
        $discount = round((float) $this->discount, 2);
        $taxableSubtotal = max(0, $subtotal - $discount);
        $vat = $this->includeVat ? round($taxableSubtotal * .16, 2) : 0;
        return ['subtotal' => $subtotal, 'discount' => $discount, 'vat' => $vat, 'total' => $taxableSubtotal + $vat];
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'billTo', 'items', 'productSearch', 'discount']);
        $this->productSearchIndex = null;
        $this->issuedAt = now()->toDateString();
        $this->quotationNumber = $this->newNumber();
        $this->includeVat = false;
        $this->terms = config('quotation.terms') ?? '';
        $this->resetValidation();
    }

    private function newNumber(): string
    {
        return 'QT-'.now()->format('ymd').'-'.strtoupper(substr((string) \Illuminate\Support\Str::ulid(), -6));
    }

    public function render()
    {
        Quotation::whereNotIn('status', ['accepted', 'converted', 'rejected', 'expired'])->whereDate('expires_at', '<', today())->update(['status' => 'expired']);
        $products = Product::query()->where('is_active', true)->when($this->productSearch, fn ($q) => $q->where('name', 'like', '%'.$this->productSearch.'%'))->orderBy('name')->limit(10)->get(['id','name','selling_price']);
        $quotations = Quotation::with(['attendant','order'])->withSum('items', 'amount')->when($this->search, fn ($q) => $q->where(fn ($x) => $x->where('quotation_number','like','%'.$this->search.'%')->orWhere('bill_to','like','%'.$this->search.'%')))->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))->latest()->paginate(15);
        return view('livewire.quotations', ['quotations' => $quotations, 'products' => $products, 'totals' => $this->totals()])->layout('layouts.app', ['title' => 'Quotations']);
    }
}
