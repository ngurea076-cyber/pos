<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Services\RecordCorrectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $period = '';
    public ?Order $order = null;
    public bool $showEditForm = false, $showInvalidationForm = false;
    public string $customerName = '', $customerPhone = '', $customerSource = 'walkin', $paymentMethod = 'cash', $notes = '', $discount = '0', $correctionReason = '';
    public array $editItems = [];

    public function mount(?Order $order = null): void
    {
        $this->order = $order?->load('items', 'attendant');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedPeriod(): void { $this->resetPage(); }

    public function openEdit(): void
    {
        abort_unless($this->order,404);
        app(RecordCorrectionService::class)->assertEditable($this->order);
        $this->customerName=$this->order->customer_name??''; $this->customerPhone=$this->order->customer_phone??''; $this->customerSource=$this->order->customer_source??'walkin'; $this->paymentMethod=$this->order->payment_method; $this->notes=$this->order->notes??''; $this->discount=(string)$this->order->discount; $this->correctionReason='';
        $this->editItems=$this->order->items->mapWithKeys(fn($item)=>[$item->id=>['unit_price'=>(string)$item->unit_price,'warranty_months'=>(int)($item->warranty_months??0)]])->all();
        $this->showEditForm=true;
    }

    public function saveCorrection(): void
    {
        abort_unless($this->order,404);
        $data=$this->validate([
            'customerName'=>['required','string','max:255'], 'customerPhone'=>['nullable','string','max:30'],
            'customerSource'=>['required',Rule::in(['walkin','instagram','tiktok','returning','reseller'])],
            'paymentMethod'=>['required',Rule::in(['cash','mpesa','card','bank'])], 'notes'=>['nullable','string','max:2000'],
            'discount'=>['required','numeric','min:0'], 'correctionReason'=>['required','string','max:500'],
            'editItems'=>['required','array'], 'editItems.*.unit_price'=>['required','numeric','min:0.01'], 'editItems.*.warranty_months'=>['required','integer','min:0','max:120'],
        ]);
        DB::transaction(function()use($data){
            $order=Order::with('items')->lockForUpdate()->findOrFail($this->order->id);
            app(RecordCorrectionService::class)->assertEditable($order);
            foreach($order->items as $item){
                $itemData=$data['editItems'][$item->id]??null; abort_unless($itemData,422,'Missing item correction data.');
                $item->update(['unit_price'=>$itemData['unit_price'],'line_total'=>$itemData['unit_price'],'warranty_months'=>$itemData['warranty_months']]);
            }
            $subtotal=(float)$order->items()->sum('line_total');
            abort_if((float)$data['discount']>$subtotal,422,'Discount cannot exceed subtotal.');
            app(RecordCorrectionService::class)->edit($order,[
                'customer_name'=>trim($data['customerName']), 'customer_phone'=>filled($data['customerPhone'])?trim($data['customerPhone']):null,
                'customer_source'=>$data['customerSource'], 'payment_method'=>$data['paymentMethod'], 'notes'=>filled($data['notes'])?trim($data['notes']):null,
                'subtotal'=>$subtotal, 'discount'=>$data['discount'], 'total'=>max(0,$subtotal-(float)$data['discount']),
            ],trim($data['correctionReason']));
        });
        $this->showEditForm=false; $this->order=Order::with('items','attendant')->find($this->order->id); session()->flash('status','Order corrected.');
    }

    public function invalidateOrder(): void
    {
        abort_unless(auth()->user()?->isAdmin(),403); abort_unless($this->order,404);
        $data=$this->validate(['correctionReason'=>['required','string','max:500']]);
        DB::transaction(function()use($data){
            $order=Order::with('items')->lockForUpdate()->findOrFail($this->order->id);
            app(RecordCorrectionService::class)->invalidate($order,trim($data['correctionReason']));
            foreach($order->items as $item){
                if(!$item->product_serial_id) continue;
                $serial=ProductSerial::whereKey($item->product_serial_id)->lockForUpdate()->first();
                if($serial && $serial->status==='sold' && (int)$serial->order_id===$order->id){
                    $serial->update(['status'=>'in_stock','order_id'=>null,'sold_at'=>null,'reseller_id'=>null,'takeout_id'=>null]);
                    Product::whereKey($serial->product_id)->increment('stock');
                }
            }
            InventoryRecord::where('type','sale')->whereIn('product_serial_id',$order->items->pluck('product_serial_id')->filter())->where('is_invalid',false)->get()->each(fn($record)=>app(RecordCorrectionService::class)->invalidate($record,'Related order invalidated: '.trim($data['correctionReason'])));
        });
        $this->showInvalidationForm=false; $this->order=Order::with('items','attendant')->find($this->order->id); session()->flash('status','Order deleted/invalidated and serials released back to stock.');
    }

    public function render()
    {
        $days = in_array($this->period, ['1', '3', '7', '15', '30'], true) ? (int) $this->period : null;
        $orders = Order::latest()
            ->when($days, fn ($query) => $query->where('created_at', '>=', $days === 1 ? today()->startOfDay() : today()->subDays($days - 1)->startOfDay()))
            ->when($this->search, fn ($query) => $query->where(fn ($search) => $search
                ->where('order_number', 'like', '%'.$this->search.'%')
                ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                ->orWhere('customer_phone', 'like', '%'.$this->search.'%')));

        return view('livewire.orders', ['orders' => $orders->paginate(20)])
            ->layout('layouts.app', ['title' => 'Orders']);
    }
}
