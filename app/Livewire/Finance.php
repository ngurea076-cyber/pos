<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\SerialPurchaseTerm;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Finance extends Component
{
    #[Url] public string $action = 'status';
    public string $serialSearch = '';
    public string $supplierId = '';
    public string $productId = '';
    public array $serialIds = [];
    public bool $selectAll = false;
    public string $buyingPrice = '';
    public string $dueDate = '';
    public string $notes = '';
    public string $paymentAmount = '';
    public string $paymentNotes = '';

    public function boot(): void { abort_unless(auth()->user()?->isAdmin(), 403); }

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        if (! in_array($this->action, ['status', 'price', 'payment'], true)) $this->action = 'status';
    }

    public function chooseAction(string $action): void
    {
        abort_unless(in_array($action, ['status', 'price', 'payment'], true), 404);
        $this->action = $action;
        $this->reset(['supplierId', 'productId', 'serialIds', 'selectAll', 'buyingPrice', 'dueDate', 'notes', 'paymentAmount', 'paymentNotes']);
        $this->resetValidation();
    }

    public function closeAction(): void
    {
        $this->action = 'status';
        $this->reset(['supplierId', 'productId', 'serialIds', 'selectAll', 'buyingPrice', 'dueDate', 'notes', 'paymentAmount', 'paymentNotes']);
        $this->resetValidation();
    }

    public function updatedSupplierId(): void { $this->reset(['productId', 'serialIds', 'selectAll']); }
    public function updatedProductId(): void { $this->reset(['serialIds', 'selectAll']); }
    public function updatedSerialIds(): void { $this->selectAll = $this->serialOptions()->isNotEmpty() && count($this->serialIds) === $this->serialOptions()->count(); }
    public function updatedSelectAll(bool $selected): void { $this->serialIds = $selected ? $this->serialOptions()->pluck('id')->map(fn ($id)=>(string)$id)->all() : []; }

    public function searchPaymentStatus(): void
    {
        $data = $this->validate(['serialSearch'=>['required', 'string', 'max:255']]);
        $serial = ProductSerial::where('serial', trim($data['serialSearch']))->first();
        if (! $serial) {
            $this->addError('serialSearch', 'No product was found with this serial code.');
            return;
        }
        $this->redirectRoute('finance.status', ['serial'=>$serial->id], navigate: true);
    }

    public function saveBuyingPrice(): void
    {
        $data = $this->validate([
            'supplierId'=>['required', 'exists:suppliers,id'],
            'productId'=>['required', 'exists:products,id'],
            'serialIds'=>['required', 'array', 'min:1'],
            'serialIds.*'=>['integer', 'exists:product_serials,id'],
            'buyingPrice'=>['required', 'numeric', 'min:0.01'],
            'dueDate'=>['nullable', 'date'],
            'notes'=>['nullable', 'string', 'max:2000'],
        ]);

        $serials = ProductSerial::withSum(['paymentAllocations as valid_payment_allocations_sum_amount'=>fn($query)=>$query->whereHas('payment',fn($payment)=>$payment->where('is_invalid',false))], 'amount')
            ->whereIn('id', $data['serialIds'])->where('supplier_id', $data['supplierId'])->where('product_id', $data['productId'])->get();
        abort_unless($serials->count() === count(array_unique($data['serialIds'])), 422, 'One or more selected serials do not match the supplier and product.');
        if ($serials->contains(fn ($serial)=>(float)$serial->valid_payment_allocations_sum_amount > (float)$data['buyingPrice'])) {
            $this->addError('buyingPrice', 'The buying price cannot be lower than payments already recorded for a selected serial.');
            return;
        }

        DB::transaction(function () use ($serials, $data) {
            foreach ($serials as $serial) {
                SerialPurchaseTerm::updateOrCreate(
                    ['product_serial_id'=>$serial->id],
                    ['supplier_id'=>$data['supplierId'], 'buying_price'=>$data['buyingPrice'], 'due_date'=>$data['dueDate'] ?: null, 'notes'=>$data['notes'] ?: null, 'set_by'=>auth()->id()]
                );
            }
        });

        $count = $serials->count();
        $this->reset(['serialIds', 'selectAll', 'buyingPrice', 'dueDate', 'notes']);
        $this->action = 'status';
        session()->flash('status', "Buying price saved for {$count} serial".($count === 1 ? '.' : 's.'));
    }

    public function makePayment(): void
    {
        $data = $this->validate([
            'supplierId'=>['required', 'exists:suppliers,id'],
            'productId'=>['required', 'exists:products,id'],
            'serialIds'=>['required', 'array', 'min:1'],
            'serialIds.*'=>['integer', 'exists:product_serials,id'],
            'paymentAmount'=>['required', 'numeric', 'min:0.01'],
            'paymentNotes'=>['nullable', 'string', 'max:2000'],
        ]);

        $serials = ProductSerial::with(['purchaseTerm', 'paymentAllocations'])
            ->whereIn('id', $data['serialIds'])->where('product_id', $data['productId'])
            ->whereHas('purchaseTerm', fn ($query)=>$query->where('supplier_id', $data['supplierId']))
            ->orderBy('id')->get();
        abort_unless($serials->count() === count(array_unique($data['serialIds'])), 422, 'One or more selected serials do not have buying prices for this supplier.');

        $outstanding = $serials->sum(fn ($serial)=>max(0, (float)$serial->purchaseTerm->buying_price - (float)$serial->paymentAllocations->reject(fn($allocation)=>$allocation->payment?->is_invalid)->sum('amount')));
        if ((float)$data['paymentAmount'] > $outstanding + 0.001) {
            $this->addError('paymentAmount', 'Amount exceeds the selected serials’ outstanding balance of KES '.number_format($outstanding, 2).'.');
            return;
        }

        DB::transaction(function () use ($serials, $data) {
            $payment = SupplierPayment::create(['supplier_id'=>$data['supplierId'], 'product_id'=>$data['productId'], 'amount'=>$data['paymentAmount'], 'notes'=>$data['paymentNotes'] ?: null, 'paid_at'=>now(), 'recorded_by'=>auth()->id()]);
            $remaining = (float)$data['paymentAmount'];
            foreach ($serials as $serial) {
                if ($remaining <= 0.001) break;
                $alreadyPaid = (float)SupplierPaymentAllocation::where('product_serial_id', $serial->id)->whereHas('payment',fn($payment)=>$payment->where('is_invalid',false))->lockForUpdate()->sum('amount');
                $balance = max(0, (float)$serial->purchaseTerm->buying_price - $alreadyPaid);
                $allocation = min($balance, $remaining);
                if ($allocation > 0) SupplierPaymentAllocation::create(['supplier_payment_id'=>$payment->id, 'product_serial_id'=>$serial->id, 'amount'=>round($allocation, 2)]);
                $remaining = round($remaining - $allocation, 2);
            }
        });

        $this->reset(['serialIds', 'selectAll', 'paymentAmount', 'paymentNotes']);
        $this->action = 'status';
        session()->flash('status', 'Supplier payment recorded successfully.');
    }

    private function serialOptions(): Collection
    {
        if (! $this->supplierId || ! $this->productId) return collect();
        $query = ProductSerial::with('purchaseTerm')->withSum(['paymentAllocations as valid_payment_allocations_sum_amount'=>fn($query)=>$query->whereHas('payment',fn($payment)=>$payment->where('is_invalid',false))], 'amount')->where('product_id', $this->productId);
        if ($this->action === 'payment') {
            return $query->whereHas('purchaseTerm', fn ($q)=>$q->where('supplier_id', $this->supplierId))->orderBy('serial')->get()
                ->filter(fn ($serial)=>(float)$serial->valid_payment_allocations_sum_amount + 0.001 < (float)$serial->purchaseTerm->buying_price)->values();
        }
        return $query->where('supplier_id', $this->supplierId)->orderBy('serial')->get();
    }

    private function productOptions(): Collection
    {
        if (! $this->supplierId) return collect();
        return Product::whereHas('serials', function ($query) {
            if ($this->action === 'payment') $query->whereHas('purchaseTerm', fn ($q)=>$q->where('supplier_id', $this->supplierId));
            else $query->where('supplier_id', $this->supplierId);
        })->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.finance', [
            'suppliers'=>Supplier::orderBy('name')->get(),
            'products'=>$this->productOptions(),
            'serials'=>$this->serialOptions(),
            'states'=>['in_stock'=>'In stock', 'with_reseller'=>'Take-out', 'sold'=>'Sold', 'returned_to_supplier'=>'Returned to supplier'],
        ])->layout('layouts.app', ['title'=>'Finance']);
    }
}
