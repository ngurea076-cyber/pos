<?php

namespace App\Livewire;

use App\Models\{InventoryRecord, Order, OrderItem, Product, ProductSerial, Reseller, ResellerTakeout, StockIntake, Supplier};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ResellerTakeoutService;
use App\Services\RecordCorrectionService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Inventory extends Component
{
    use WithPagination;

    #[Url] public string $filter = 'all';
    #[Url] public string $stateFilter = '';
    #[Url] public string $supplierFilter = '';
    #[Url] public string $resellerFilter = '';
    #[Url] public string $recordsSearch = '';
    #[Url] public string $periodFilter = '';
    public bool $showFilters = false;
    public ?int $viewedRecordId = null;
    public string $serialSearch = '';
    public bool $showSerialSearch = false;
    public string $section = 'actions';
    public ?string $action = null;
    public ?int $productId = null, $supplierId = null, $resellerId = null, $serialId = null;
    public string $prefix = '', $notes = '', $intakeDate = '';
    public string $productSearch = '';
    public bool $showProductSuggestions = false;
    public string $supplierSearch = '', $resellerSearch = '';
    public bool $showSupplierSuggestions = false, $showResellerSuggestions = false;
    public array $serialCodes = [];
    public string $serialCodeInput = '';
    public array $takeoutSerialIds = [];
    public array $supplierReturnSerialIds = [];
    public string $customerReturnOrderNumber = '';
    public ?int $customerReturnOrderId = null;
    public string $returnMessage = '';
    public bool $showCorrectionForm = false, $showInvalidationForm = false;
    public string $correctionReason = '', $correctionNotes = '', $correctionDate = '';

    public function mount(string $section = 'actions'): void
    {
        abort_unless(in_array($section,['actions','records'],true),404);
        $this->section=$section;
        $this->intakeDate=today()->toDateString();
        $requestedAction=request()->query('action');
        if($section==='actions' && is_string($requestedAction) && in_array($requestedAction,['stock_intake','reseller_takeout','wholesaler_return'],true)) $this->openAction($requestedAction);
    }
    public function updatedFilter(): void { $this->resetPage(); }
    public function updatedStateFilter(): void { $this->resetPage(); }
    public function updatedSupplierFilter(): void { $this->resetPage(); }
    public function updatedResellerFilter(): void { $this->resetPage(); }
    public function updatedRecordsSearch(): void { $this->resetPage(); }
    public function updatedPeriodFilter(): void { $this->resetPage(); }
    public function clearFilters(): void { $this->reset(['stateFilter','supplierFilter','resellerFilter','recordsSearch','periodFilter']); $this->resetPage(); }
    public function viewRecord(int $recordId): void { $this->viewedRecordId=$recordId; $this->correctionReason='__idle__'; }
    public function closeRecord(): void { $this->viewedRecordId=null; }
    public function updatedCorrectionReason(string $value): void
    {
        if($value==='' && $this->viewedRecordId && auth()->user()?->isAdmin() && !$this->showCorrectionForm) $this->showInvalidationForm=true;
    }
    public function editViewedRecord(): void
    {
        $record=InventoryRecord::findOrFail($this->viewedRecordId);
        app(RecordCorrectionService::class)->assertEditable($record);
        $this->correctionNotes=$record->notes??'';
        $this->correctionDate=$record->happened_at->format('Y-m-d\TH:i');
        $this->correctionReason='';
        $this->showCorrectionForm=true;
    }
    public function saveViewedRecordCorrection(): void
    {
        $data=$this->validate([
            'correctionReason'=>'required|string|max:500', 'correctionNotes'=>'nullable|string|max:2000',
            'correctionDate'=>'required|date',
        ]);
        $record=InventoryRecord::findOrFail($this->viewedRecordId);
        app(RecordCorrectionService::class)->edit($record,[
            'notes'=>filled($data['correctionNotes'])?trim($data['correctionNotes']):null,
            'happened_at'=>$data['correctionDate'],
        ],trim($data['correctionReason']));
        $this->showCorrectionForm=false;
        session()->flash('status','Inventory record corrected.');
    }
    public function invalidateViewedRecord(): void
    {
        $data=$this->validate(['correctionReason'=>'required|string|max:500']);
        $record=InventoryRecord::with('serial')->findOrFail($this->viewedRecordId);
        DB::transaction(function()use($record,$data){
            if($record->type==='stock_intake'){
                preg_match('/^INTAKE-(\d+)$/',$record->reference??'',$match);
                abort_unless(isset($match[1]),422,'The intake link could not be verified.');
                $serials=ProductSerial::where('intake_id',(int)$match[1])->lockForUpdate()->get();
                abort_if($serials->contains(fn($serial)=>$serial->status!=='in_stock'||InventoryRecord::where('product_serial_id',$serial->id)->where('is_invalid',false)->exists()),422,'This intake has subsequent movements and cannot be invalidated. Reverse those movements first.');
                abort_if(DB::table('supplier_payment_allocations')->whereIn('product_serial_id',$serials->pluck('id'))->exists(),422,'This intake has supplier payments and cannot be invalidated. Correct those payments first.');
                Product::whereKey($record->product_id)->decrement('stock',$serials->count());
                ProductSerial::whereIn('id',$serials->pluck('id'))->delete();
            }elseif($record->type==='reseller_takeout'){
                abort_unless($record->serial?->status==='with_reseller',422,'This take-out has already moved and cannot be invalidated.');
                $record->serial->update(['status'=>'in_stock','reseller_id'=>null,'takeout_id'=>null]);
                Product::whereKey($record->product_id)->increment('stock');
            }elseif($record->type==='wholesaler_return'){
                abort_unless($record->serial?->status==='returned_to_supplier',422,'This serial has already moved and cannot be invalidated.');
                $record->serial->update(['status'=>'in_stock']);
                Product::whereKey($record->product_id)->increment('stock');
            }elseif($record->type==='customer_return'){
                abort_unless($record->serial?->status==='in_stock'&&$record->serial->order_id,422,'This return has already moved and cannot be invalidated.');
                $record->serial->update(['status'=>'sold']);
                Product::whereKey($record->product_id)->decrement('stock');
            }elseif($record->type==='reseller_return'){
                abort_unless($record->serial?->status==='in_stock',422,'This return has already moved and cannot be invalidated.');
                $record->serial->update(['status'=>'with_reseller','reseller_id'=>$record->reseller_id]);
                Product::whereKey($record->product_id)->decrement('stock');
            }else{
                abort(422,'Invalidate the related order to reverse a sale.');
            }
            app(RecordCorrectionService::class)->invalidate($record,trim($data['correctionReason']));
        });
        $this->showCorrectionForm=false;$this->showInvalidationForm=false;$this->viewedRecordId=null;
        session()->flash('status','Inventory record invalidated and its stock effect reversed.');
    }
    public function sellTakeoutSerial(int $serialId): void
    {
        $serial=ProductSerial::whereKey($serialId)->where('status','with_reseller')->firstOrFail();
        $this->redirectRoute('pos',['reseller_serial'=>$serial->id],navigate:true);
    }
    public function returnTakeoutSerial(int $serialId): void
    {
        app(ResellerTakeoutService::class)->returnToStock($serialId,auth()->id());
        $this->viewedRecordId=null;
        session()->flash('status','Product returned to stock.');
        $this->resetPage();
    }
    public function searchSerial(): void
    {
        $this->validate(['serialSearch'=>'required|string|max:255']);
        $serial=ProductSerial::where('serial',trim($this->serialSearch))->first();
        if(!$serial){$this->addError('serialSearch','No serial code matched your search.');return;}
        $this->redirectRoute('inventory.serial-history',['serial'=>$serial->id],navigate:true);
    }
    public function openSerialSearch(): void { $this->serialSearch='';$this->showSerialSearch=true;$this->resetValidation('serialSearch'); }
    public function updatedProductId(): void { if($this->action==='reseller_takeout') $this->takeoutSerialIds=[]; if($this->action==='wholesaler_return') $this->supplierReturnSerialIds=[]; }
    public function updatedProductSearch(): void
    {
        $this->showProductSuggestions=true;
        if($this->productId && Product::whereKey($this->productId)->value('name')!==trim($this->productSearch)){$this->productId=null;$this->updatedProductId();}
    }
    public function selectInventoryProduct(int $productId): void
    {
        $product=Product::findOrFail($productId);
        if($this->action!=='stock_intake') abort_unless($product->availableSerials()->exists(),422,'This product has no available stock.');
        $this->productId=$product->id;$this->productSearch=$product->name;$this->showProductSuggestions=false;$this->updatedProductId();$this->resetValidation('productId');
    }
    public function updatedSupplierSearch(): void { $this->showSupplierSuggestions=true; if($this->supplierId&&Supplier::whereKey($this->supplierId)->value('name')!==trim($this->supplierSearch))$this->supplierId=null; }
    public function selectInventorySupplier(int $supplierId): void { $supplier=Supplier::findOrFail($supplierId);$this->supplierId=$supplier->id;$this->supplierSearch=$supplier->name;$this->showSupplierSuggestions=false;$this->resetValidation('supplierId'); }
    public function updatedResellerSearch(): void { $this->showResellerSuggestions=true; if($this->resellerId&&Reseller::whereKey($this->resellerId)->value('name')!==trim($this->resellerSearch))$this->resellerId=null; }
    public function selectInventoryReseller(int $resellerId): void { $reseller=Reseller::findOrFail($resellerId);$this->resellerId=$reseller->id;$this->resellerSearch=$reseller->name;$this->showResellerSuggestions=false;$this->resetValidation('resellerId'); }
    public function addSerialCode(): void
    {
        $code=trim($this->serialCodeInput);
        if($code===''){$this->addError('serialCodeInput','Enter a serial code first.');return;}
        $full=$this->prefix.$code;
        if(collect($this->serialCodes)->contains(fn($existing)=>strcasecmp($this->prefix.$existing,$full)===0)||ProductSerial::where('serial',$full)->exists()){$this->addError('serialCodeInput','This serial code already exists.');return;}
        $this->serialCodes[]=$code;$this->serialCodeInput='';$this->resetValidation(['serialCodeInput','serialCodes']);
    }
    public function removeSerialCode(int $index): void { unset($this->serialCodes[$index]);$this->serialCodes=array_values($this->serialCodes); }
    public function openAction(string $action): void { abort_unless(in_array($action,['stock_intake','reseller_takeout','wholesaler_return','customer_return'],true),404); $this->resetForm(); $this->action=$action; }
    public function closeAction(): void { $this->resetForm(); $this->action=null; }

    public function saveIntake(): void
    {
        $data=$this->validate(['productId'=>'required|exists:products,id','supplierId'=>'required|exists:suppliers,id','intakeDate'=>'required|date','serialCodes'=>'required|array|min:1','serialCodes.*'=>'nullable|string|max:255']);
        $parts=collect($data['serialCodes'])->map(fn($s)=>trim($s))->filter()->unique()->values();
        abort_if($parts->isEmpty(),422,'Enter at least one serial code.');
        abort_if(ProductSerial::whereIn('serial',$parts->map(fn($part)=>$this->prefix.$part))->exists(),422,'One or more serial codes already exist.');
        DB::transaction(function()use($data,$parts){
            $intake=StockIntake::create(['product_id'=>$data['productId'],'supplier_id'=>$data['supplierId'],'prefix'=>$this->prefix?:null,'quantity'=>$parts->count(),'intake_date'=>$data['intakeDate'],'notes'=>null,'created_by'=>auth()->id()]);
            foreach($parts as $suffix) ProductSerial::create(['product_id'=>$data['productId'],'supplier_id'=>$data['supplierId'],'intake_id'=>$intake->id,'prefix'=>$this->prefix?:null,'serial_suffix'=>$suffix,'serial'=>$this->prefix.$suffix]);
            Product::whereKey($data['productId'])->increment('stock',$parts->count());
            InventoryRecord::create(['type'=>'stock_intake','product_id'=>$data['productId'],'supplier_id'=>$data['supplierId'],'quantity'=>$parts->count(),'reference'=>'INTAKE-'.$intake->id,'notes'=>null,'created_by'=>auth()->id(),'happened_at'=>$data['intakeDate'].' '.now()->format('H:i:s')]);
        });
        $this->finish('Stock intake recorded.');
    }

    public function saveTakeout(): void
    {
        $data=$this->validate(['resellerId'=>'required|exists:resellers,id','productId'=>'required|exists:products,id','takeoutSerialIds'=>'required|array|min:1','takeoutSerialIds.*'=>'required|exists:product_serials,id']);
        DB::transaction(function()use($data){
            $serials=ProductSerial::whereIn('id',$data['takeoutSerialIds'])->where('product_id',$data['productId'])->where('status','in_stock')->lockForUpdate()->get();
            abort_unless($serials->count()===count(array_unique($data['takeoutSerialIds'])),409,'One or more serials are no longer available.');
            $out=ResellerTakeout::create(['reseller_id'=>$data['resellerId'],'takeout_date'=>today(),'notes'=>$this->notes?:null,'created_by'=>auth()->id()]);
            foreach($serials as $serial){
                $serial->update(['status'=>'with_reseller','reseller_id'=>$data['resellerId'],'takeout_id'=>$out->id]);
                InventoryRecord::create(['type'=>'reseller_takeout','product_id'=>$serial->product_id,'product_serial_id'=>$serial->id,'supplier_id'=>$serial->supplier_id,'reseller_id'=>$data['resellerId'],'quantity'=>-1,'reference'=>'TAKEOUT-'.$out->id.'-'.$serial->id,'notes'=>$this->notes?:null,'created_by'=>auth()->id(),'happened_at'=>now()]);
            }
            Product::whereKey($data['productId'])->decrement('stock',$serials->count());
        });
        $this->finish('Reseller take-out recorded.');
    }

    public function saveWholesalerReturn(): void
    {
        $data=$this->validate(['supplierId'=>'required|exists:suppliers,id','productId'=>'required|exists:products,id','supplierReturnSerialIds'=>'required|array|min:1','supplierReturnSerialIds.*'=>'required|exists:product_serials,id']);
        DB::transaction(function()use($data){
            $serials=ProductSerial::whereIn('id',$data['supplierReturnSerialIds'])->where('product_id',$data['productId'])->where('supplier_id',$data['supplierId'])->where('status','in_stock')->lockForUpdate()->get();
            abort_unless($serials->count()===count(array_unique($data['supplierReturnSerialIds'])),409,'One or more serials are no longer available.');
            foreach($serials as $serial){
                $serial->update(['status'=>'returned_to_supplier','supplier_id'=>$data['supplierId']]);
                InventoryRecord::create(['type'=>'wholesaler_return','product_id'=>$serial->product_id,'product_serial_id'=>$serial->id,'supplier_id'=>$data['supplierId'],'quantity'=>-1,'reference'=>'SUPPLIER-RETURN-'.$serial->id.'-'.now()->format('YmdHis'),'notes'=>$this->notes?:null,'created_by'=>auth()->id(),'happened_at'=>now()]);
            }
            Product::whereKey($data['productId'])->decrement('stock',$serials->count());
        });
        $this->finish('Supplier return recorded.');
    }

    public function findCustomerReturnOrder(): void
    {
        $this->validate(['customerReturnOrderNumber'=>'required|string|max:100']);
        $order=Order::where('is_invalid',false)->where('order_number',trim($this->customerReturnOrderNumber))->first();
        if(!$order){$this->addError('customerReturnOrderNumber','No order was found with this order number.');$this->customerReturnOrderId=null;return;}
        $this->customerReturnOrderId=$order->id;
        $this->customerReturnOrderNumber=$order->order_number;
        $this->returnMessage='';
        $this->resetValidation('customerReturnOrderNumber');
    }

    public function returnOrderItem(int $orderItemId): void
    {
        abort_unless($this->customerReturnOrderId,422,'Find an order first.');
        $item=OrderItem::whereKey($orderItemId)->where('order_id',$this->customerReturnOrderId)->firstOrFail();
        abort_unless($item->product_serial_id,422,'This order item has no serialized unit.');
        DB::transaction(function()use($item){
            $serial=ProductSerial::whereKey($item->product_serial_id)->where('status','sold')->lockForUpdate()->firstOrFail();
            $serial->update(['status'=>'in_stock']);
            Product::whereKey($serial->product_id)->increment('stock');
            InventoryRecord::create(['type'=>'customer_return','product_id'=>$serial->product_id,'product_serial_id'=>$serial->id,'supplier_id'=>$serial->supplier_id,'quantity'=>1,'reference'=>'CUSTOMER-RETURN-'.$item->order_id.'-'.$serial->id,'notes'=>$this->notes?:null,'created_by'=>auth()->id(),'happened_at'=>now()]);
        });
        $this->returnMessage='Product returned to stock successfully.';
    }

    private function finish(string $message): void { $this->closeAction(); session()->flash('status',$message); $this->resetPage(); }
    private function resetForm(): void { $this->reset(['productId','productSearch','showProductSuggestions','supplierId','supplierSearch','showSupplierSuggestions','resellerId','resellerSearch','showResellerSuggestions','serialId','prefix','notes','serialCodeInput','takeoutSerialIds','supplierReturnSerialIds','customerReturnOrderNumber','customerReturnOrderId','returnMessage']); $this->serialCodes=[]; $this->intakeDate=today()->toDateString(); $this->resetValidation(); }

    public function render()
    {
        $records=InventoryRecord::with('product.serials','serial','supplier','reseller','user')
            ->latest('happened_at')
            ->when($this->stateFilter,fn($q)=>$q->whereHas('serial',fn($s)=>$s->where('status',$this->stateFilter)))
            ->when($this->supplierFilter,fn($q)=>$q->where(fn($x)=>$x->where('supplier_id',$this->supplierFilter)->orWhereHas('serial',fn($s)=>$s->where('supplier_id',$this->supplierFilter))))
            ->when($this->resellerFilter,fn($q)=>$q->where(fn($x)=>$x->where('reseller_id',$this->resellerFilter)->orWhereHas('serial',fn($s)=>$s->where('reseller_id',$this->resellerFilter))))
            ->when($this->recordsSearch,fn($q)=>$q->where(fn($x)=>$x->whereHas('product',fn($p)=>$p->where('name','like','%'.$this->recordsSearch.'%'))->orWhereHas('serial',fn($s)=>$s->where('serial','like','%'.$this->recordsSearch.'%'))))
            ->when($this->periodFilter,function($q){$days=(int)$this->periodFilter;$q->where('happened_at','>=',$days===1?today()->startOfDay():today()->subDays($days-1)->startOfDay());})
            ->paginate(20);
        $customerReturnOrder=$this->customerReturnOrderId?Order::with('items.product','items.serial')->find($this->customerReturnOrderId):null;
        $viewedRecord=$this->viewedRecordId?InventoryRecord::with('product.serials','serial.supplier','serial.order','supplier','reseller','user')->find($this->viewedRecordId):null;
        $viewedSerials=collect();
        if($viewedRecord){
            if($viewedRecord->type==='stock_intake' && preg_match('/^INTAKE-(\d+)$/',$viewedRecord->reference??'',$match)){
                $viewedSerials=ProductSerial::where('intake_id',(int)$match[1])->orderBy('serial')->get();
            }elseif($viewedRecord->serial){
                $viewedSerials=collect([$viewedRecord->serial]);
            }
        }
        $productSource=$this->action==='stock_intake'?Product::orderBy('name')->get():Product::whereHas('availableSerials')->orderBy('name')->get();
        $productSuggestions=$productSource->filter(fn($product)=>$this->productSearch===''||str_contains(strtolower($product->name),strtolower($this->productSearch)))->take(8);
        $supplierSuggestions=Supplier::orderBy('name')->get()->filter(fn($supplier)=>$this->supplierSearch===''||str_contains(strtolower($supplier->name),strtolower($this->supplierSearch)))->take(8);
        $resellerSuggestions=Reseller::orderBy('name')->get()->filter(fn($reseller)=>$this->resellerSearch===''||str_contains(strtolower($reseller->name),strtolower($this->resellerSearch)))->take(8);
        return view('livewire.inventory',[
            'records'=>$records,'products'=>Product::orderBy('name')->get(),'inStockProducts'=>Product::whereHas('availableSerials')->orderBy('name')->get(),'suppliers'=>Supplier::orderBy('name')->get(),'resellers'=>Reseller::orderBy('name')->get(),
            'inStockSerials'=>ProductSerial::with('product')->where('status','in_stock')->orderBy('serial')->limit(300)->get(),
            'selectedProductSerials'=>$this->productId?ProductSerial::where('product_id',$this->productId)->where('status','in_stock')->orderBy('serial')->get():collect(),
            'soldSerials'=>ProductSerial::with('product')->where('status','sold')->orderBy('serial')->limit(300)->get(),
            'customerReturnOrder'=>$customerReturnOrder,'viewedRecord'=>$viewedRecord,'viewedSerials'=>$viewedSerials,'productSuggestions'=>$productSuggestions,'supplierSuggestions'=>$supplierSuggestions,'resellerSuggestions'=>$resellerSuggestions,
        ])->layout('layouts.app',['title'=>'Inventory']);
    }
}
