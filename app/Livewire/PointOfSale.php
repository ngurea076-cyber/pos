<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PointOfSale extends Component
{
    public int $step = 1;
    public string $customerName = '';
    public string $customerPhone = '';
    public string $customerSource = 'walkin';
    public string $search = '';
    public ?int $selectedProductId = null;
    public ?int $selectedSerialId = null;
    public int $warrantyMonths = 0;
    public string $price = '';
    public string $paymentMethod = 'cash';
    public string $notes = '';
    public float $discount = 0;
    public array $cart = [];
    public bool $customProductOpen = false;
    public string $customProductName = '';
    public ?int $customSupplierId = null;
    public string $customSerial = '';
    public int $customWarrantyMonths = 0;
    public string $customPrice = '';
    public ?int $resellerSaleSerialId = null;

    public function mount(): void
    {
        $serialId = request()->integer('reseller_serial');
        if (! $serialId) return;

        $serial = ProductSerial::with(['product', 'reseller'])
            ->whereKey($serialId)
            ->where('status', 'with_reseller')
            ->whereNotNull('reseller_id')
            ->firstOrFail();

        $this->resellerSaleSerialId = $serial->id;
        $this->customerName = $serial->reseller->name;
        $this->customerSource = 'reseller';
        $this->selectedProductId = $serial->product_id;
        $this->selectedSerialId = $serial->id;
        $this->price = $serial->product->selling_price > 0 ? (string) $serial->product->selling_price : '';
        $this->step = 2;
    }

    public function customerNext(): void
    {
        $this->validate([
            'customerName' => ['required', 'string', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:30'],
            'customerSource' => ['required', Rule::in(['walkin', 'instagram', 'tiktok', 'returning', 'reseller'])],
        ]);
        $this->step = 2;
    }

    public function selectProduct(Product $product): void
    {
        abort_unless($product->availableSerials()->exists(), 422, 'This product has no units in stock.');
        $this->selectedProductId = $product->id;
        $this->selectedSerialId = null;
        $this->warrantyMonths = 0;
        $this->price = $product->selling_price > 0 ? (string) $product->selling_price : '';
        $this->resetValidation(['selectedSerialId', 'warrantyMonths', 'price']);
    }

    public function cancelSelection(): void
    {
        $this->reset(['selectedProductId', 'selectedSerialId', 'warrantyMonths', 'price']);
        $this->resetValidation(['selectedSerialId', 'warrantyMonths', 'price']);
    }

    public function addSelectedProduct(): void
    {
        $data = $this->validate([
            'selectedProductId' => ['required', 'exists:products,id'],
            'selectedSerialId' => ['required', 'exists:product_serials,id'],
            'warrantyMonths' => ['required', 'integer', 'min:0', 'max:120'],
            'price' => ['required', 'numeric', 'min:0.01'],
        ]);

        $fromReseller = $this->resellerSaleSerialId === (int) $data['selectedSerialId'];
        $serial = ProductSerial::with('product')
            ->whereKey($data['selectedSerialId'])
            ->where('product_id', $data['selectedProductId'])
            ->where('status', $fromReseller ? 'with_reseller' : 'in_stock')
            ->firstOrFail();

        abort_if(isset($this->cart[$serial->id]), 422, 'This serial is already in the order.');

        $this->cart[$serial->id] = [
            'serial_id' => $serial->id,
            'product_id' => $serial->product_id,
            'is_custom' => false,
            'from_reseller' => $fromReseller,
            'name' => $serial->product->name,
            'serial' => $serial->serial,
            'warranty_months' => $data['warrantyMonths'],
            'price' => (float) $data['price'],
            'cost' => 0,
        ];
        $this->cancelSelection();
        if ($fromReseller) $this->step = 3;
    }

    public function remove(string $cartKey): void { unset($this->cart[$cartKey]); }

    public function openCustomProduct(): void
    {
        $this->customProductName = trim($this->search);
        $this->customProductOpen = true;
        $this->resetValidation(['customProductName', 'customSupplierId', 'customSerial', 'customWarrantyMonths', 'customPrice']);
    }

    public function closeCustomProduct(): void
    {
        $this->customProductOpen = false;
        $this->reset(['customProductName', 'customSupplierId', 'customSerial', 'customWarrantyMonths', 'customPrice']);
        $this->customWarrantyMonths = 0;
        $this->resetValidation(['customProductName', 'customSupplierId', 'customSerial', 'customWarrantyMonths', 'customPrice']);
    }

    public function addCustomProduct(): void
    {
        $data = $this->validate([
            'customProductName' => ['required', 'string', 'max:255'],
            'customSupplierId' => ['required', 'exists:suppliers,id'],
            'customSerial' => ['required', 'string', 'max:255', Rule::unique('product_serials', 'serial')],
            'customWarrantyMonths' => ['required', 'integer', 'min:0', 'max:120'],
            'customPrice' => ['required', 'numeric', 'min:0.01'],
        ]);

        $serial = trim($data['customSerial']);
        abort_if(collect($this->cart)->contains(fn ($item) => strcasecmp($item['serial'], $serial) === 0), 422, 'This serial is already in the order.');
        $supplier = Supplier::findOrFail($data['customSupplierId']);
        $product = Product::firstOrCreate(
            ['name' => trim($data['customProductName'])],
            ['selling_price' => (float) $data['customPrice'], 'stock' => 0, 'is_active' => true]
        );
        $key = 'custom-'.Str::uuid();

        $this->cart[$key] = [
            'serial_id' => null,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'is_custom' => true,
            'name' => trim($data['customProductName']),
            'serial' => $serial,
            'warranty_months' => $data['customWarrantyMonths'],
            'price' => (float) $data['customPrice'],
            'cost' => 0,
        ];

        $this->search = '';
        $this->closeCustomProduct();
    }

    public function review(): void
    {
        abort_if(empty($this->cart), 422, 'Add at least one product to continue.');
        $this->validate([
            'discount' => ['numeric', 'min:0', 'max:'.$this->subtotal()],
            'paymentMethod' => ['required', Rule::in(['cash', 'mpesa', 'card', 'bank'])],
        ]);
        $this->step = 3;
    }

    public function checkout(): void
    {
        abort_unless($this->step === 3 && count($this->cart), 422, 'Complete the order steps first.');

        $order = DB::transaction(function () {
            $existingItems = collect($this->cart)->reject(fn ($item) => $item['is_custom'] ?? false);
            $stockIds = $existingItems->reject(fn ($item) => $item['from_reseller'] ?? false)->pluck('serial_id')->filter()->values();
            $resellerIds = $existingItems->filter(fn ($item) => $item['from_reseller'] ?? false)->pluck('serial_id')->filter()->values();
            $stockSerials = ProductSerial::whereIn('id', $stockIds)
                ->where('status', 'in_stock')->lockForUpdate()->get();
            $resellerSerials = ProductSerial::whereIn('id', $resellerIds)
                ->where('status', 'with_reseller')->lockForUpdate()->get();
            abort_unless($stockSerials->count() === $stockIds->count() && $resellerSerials->count() === $resellerIds->count(), 409, 'One or more selected units are no longer available.');
            $serials = $stockSerials->concat($resellerSerials);

            $subtotal = $this->subtotal();
            $order = Order::create([
                'customer_name' => $this->customerName,
                'customer_phone' => filled($this->customerPhone) ? trim($this->customerPhone) : null,
                'customer_source' => $this->customerSource,
                'subtotal' => $subtotal,
                'discount' => $this->discount,
                'total' => max(0, $subtotal - $this->discount),
                'payment_method' => $this->paymentMethod,
                'notes' => $this->notes ?: null,
                'attendant_id' => auth()->id(),
            ]);

            foreach ($this->cart as $item) {
                if ($item['is_custom'] ?? false) {
                    abort_if(ProductSerial::where('serial', $item['serial'])->lockForUpdate()->exists(), 409, 'The custom serial '.$item['serial'].' already exists.');
                    $product = Product::find($item['product_id']) ?? Product::firstOrCreate(
                        ['name' => $item['name']],
                        ['selling_price' => $item['price'], 'stock' => 0, 'is_active' => true]
                    );
                    $serial = ProductSerial::create([
                        'product_id' => $product->id,
                        'serial_suffix' => $item['serial'],
                        'serial' => $item['serial'],
                        'supplier_id' => $item['supplier_id'],
                        'status' => 'sold',
                        'order_id' => $order->id,
                        'sold_at' => now(),
                    ]);
                    $item['product_id'] = $product->id;
                    $item['serial_id'] = $serial->id;
                } else {
                    $serial = $serials->firstWhere('id', $item['serial_id']);
                    if (! ($item['from_reseller'] ?? false)) Product::whereKey($item['product_id'])->decrement('stock');
                }

                $privateBuyingPrice = $item['is_custom'] ?? false
                    ? 0
                    : (float) (\App\Models\SerialPurchaseTerm::where('product_serial_id', $item['serial_id'])->value('buying_price') ?? 0);

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_serial_id' => $item['serial_id'],
                    'name_snapshot' => $item['name'],
                    'serial_snapshot' => $item['serial'],
                    'warranty_months' => $item['warranty_months'],
                    'quantity' => 1,
                    'unit_price' => $item['price'],
                    'buying_price_snapshot' => $privateBuyingPrice,
                    'line_total' => $item['price'],
                ]);
                InventoryRecord::create(['type'=>'sale','product_id'=>$item['product_id'],'product_serial_id'=>$item['serial_id'],'supplier_id'=>$serial?->supplier_id,'quantity'=>-1,'reference'=>'SALE-'.$order->id.'-'.$item['serial_id'],'notes'=>$this->notes?:null,'created_by'=>auth()->id(),'happened_at'=>now()]);
            }

            ProductSerial::whereIn('id', $stockIds->concat($resellerIds))->update(['status'=>'sold', 'order_id'=>$order->id, 'sold_at'=>now()]);
            return $order;
        });

        $this->redirectRoute('orders', ['order' => $order->id], navigate: true);
    }

    public function subtotal(): float { return (float) collect($this->cart)->sum('price'); }

    public function render()
    {
        $selectedExistingIds = collect($this->cart)->reject(fn ($item) => $item['is_custom'] ?? false)->pluck('serial_id')->filter()->all();
        $products = Product::with(['availableSerials'=>fn($q)=>$q->whereNotIn('id', $selectedExistingIds)->orderBy('serial')])
            ->where('is_active', true)->whereHas('availableSerials', fn ($q) => $q->whereNotIn('id', $selectedExistingIds))
            ->when($this->search, fn($q)=>$q->where(fn($x)=>$x->where('name','like','%'.$this->search.'%')->orWhere('sku','like','%'.$this->search.'%')))
            ->when(blank($this->search), fn($q)=>$q->whereRaw('1 = 0'))
            ->orderBy('name')->limit(8)->get();
        $selectedProduct = $this->selectedProductId ? $products->firstWhere('id', $this->selectedProductId) ?? Product::with('availableSerials')->find($this->selectedProductId) : null;
        $resellerSaleSerial = $this->resellerSaleSerialId ? ProductSerial::with('reseller')->find($this->resellerSaleSerialId) : null;
        return view('livewire.point-of-sale', ['products'=>$products, 'suppliers'=>Supplier::orderBy('name')->get(), 'selectedProduct'=>$selectedProduct, 'resellerSaleSerial'=>$resellerSaleSerial, 'subtotal'=>$this->subtotal()])->layout('layouts.app', ['title'=>'New Sale']);
    }
}
