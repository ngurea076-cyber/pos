<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use App\Models\Order;
use App\Models\ProductSerial;
use App\Services\ResellerTakeoutService;
use Livewire\Component;

class SerialHistory extends Component
{
    public ProductSerial $serial;

    public function mount(ProductSerial $serial): void { $this->serial=$serial->load('product','supplier'); }

    public function sellTakeout(): void
    {
        abort_unless($this->serial->status==='with_reseller',409,'This take-out has already been resolved.');
        $this->redirectRoute('pos',['reseller_serial'=>$this->serial->id],navigate:true);
    }

    public function returnTakeout(): void
    {
        app(ResellerTakeoutService::class)->returnToStock($this->serial->id,auth()->id());
        $this->serial->refresh()->load('product','supplier');
        session()->flash('status','Product returned to stock.');
    }

    public function render()
    {
        $records=InventoryRecord::with('user','supplier','reseller')
            ->where(function($query){
                $query->where('product_serial_id',$this->serial->id);
                if($this->serial->intake_id) $query->orWhere('reference','INTAKE-'.$this->serial->intake_id);
            })->latest('happened_at')->get();
        $orderIds=$records->where('type','sale')->map(function($record){return preg_match('/^SALE-(\d+)-/',$record->reference??'',$match)?(int)$match[1]:null;})->filter()->unique();
        $orderNumbers=Order::whereIn('id',$orderIds)->pluck('order_number','id');
        $records->each(function($record)use($orderNumbers){
            if($record->type==='sale'&&preg_match('/^SALE-(\d+)-/',$record->reference??'',$match)){
                $record->sale_order_id=(int)$match[1];
                $record->sale_order_number=$orderNumbers[$record->sale_order_id]??null;
            }
        });
        return view('livewire.serial-history',['records'=>$records])->layout('layouts.app',['title'=>'Serial History']);
    }
}
