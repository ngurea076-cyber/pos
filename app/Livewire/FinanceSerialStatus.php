<?php

namespace App\Livewire;

use App\Models\ProductSerial;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use App\Services\RecordCorrectionService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FinanceSerialStatus extends Component
{
    public ProductSerial $serial;
    public bool $showPaymentEdit=false, $showPaymentInvalidation=false;
    public ?int $editingAllocationId=null, $invalidatingPaymentId=null;
    public string $paymentAmount='', $paymentDate='', $paymentNotes='', $correctionReason='';

    public function boot(): void { abort_unless(auth()->user()?->isAdmin(), 403); }

    public function mount(ProductSerial $serial): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->serial = $serial;
    }

    public function editPayment(int $allocationId): void
    {
        $allocation=SupplierPaymentAllocation::with('payment')->whereKey($allocationId)->where('product_serial_id',$this->serial->id)->firstOrFail();
        app(RecordCorrectionService::class)->assertEditable($allocation->payment);
        $this->editingAllocationId=$allocation->id; $this->paymentAmount=(string)$allocation->amount; $this->paymentDate=$allocation->payment->paid_at->format('Y-m-d\TH:i'); $this->paymentNotes=$allocation->payment->notes??''; $this->correctionReason=''; $this->showPaymentEdit=true;
    }

    public function savePaymentCorrection(): void
    {
        $data=$this->validate(['paymentAmount'=>['required','numeric','min:0.01'],'paymentDate'=>['required','date'],'paymentNotes'=>['nullable','string','max:2000'],'correctionReason'=>['required','string','max:500']]);
        DB::transaction(function()use($data){
            $allocation=SupplierPaymentAllocation::with('payment')->whereKey($this->editingAllocationId)->where('product_serial_id',$this->serial->id)->lockForUpdate()->firstOrFail();
            $serial=ProductSerial::with('purchaseTerm')->whereKey($this->serial->id)->lockForUpdate()->firstOrFail();
            $otherPaid=(float)SupplierPaymentAllocation::where('product_serial_id',$serial->id)->where('id','<>',$allocation->id)->whereHas('payment',fn($q)=>$q->where('is_invalid',false))->sum('amount');
            abort_if($serial->purchaseTerm && $otherPaid+(float)$data['paymentAmount'] > (float)$serial->purchaseTerm->buying_price + 0.001,422,'Corrected payment exceeds the buying price.');
            $diff=(float)$data['paymentAmount']-(float)$allocation->amount;
            $allocation->update(['amount'=>$data['paymentAmount']]);
            app(RecordCorrectionService::class)->edit($allocation->payment,[
                'amount'=>max(0,(float)$allocation->payment->amount+$diff), 'paid_at'=>$data['paymentDate'], 'notes'=>filled($data['paymentNotes'])?trim($data['paymentNotes']):null,
            ],trim($data['correctionReason']));
        });
        $this->showPaymentEdit=false; session()->flash('status','Payment corrected.');
    }

    public function confirmInvalidatePayment(int $paymentId): void
    {
        $payment=SupplierPayment::whereKey($paymentId)->whereHas('allocations',fn($q)=>$q->where('product_serial_id',$this->serial->id))->firstOrFail();
        abort_if($payment->is_invalid,422,'This payment is already invalidated.');
        $this->invalidatingPaymentId=$payment->id; $this->correctionReason=''; $this->showPaymentInvalidation=true;
    }

    public function invalidatePayment(): void
    {
        $data=$this->validate(['correctionReason'=>['required','string','max:500']]);
        app(RecordCorrectionService::class)->invalidate(SupplierPayment::findOrFail($this->invalidatingPaymentId),trim($data['correctionReason']));
        $this->showPaymentInvalidation=false; session()->flash('status','Payment deleted/invalidated.');
    }

    public function render()
    {
        $serial = ProductSerial::with([
            'product', 'supplier', 'purchaseTerm.supplier', 'purchaseTerm.setter',
            'paymentAllocations'=>fn ($query)=>$query->with(['payment.recorder'])->latest(),
        ])->findOrFail($this->serial->id);
        $price = (float)($serial->purchaseTerm?->buying_price ?? 0);
        $paid = (float)$serial->paymentAllocations->reject(fn($allocation)=>$allocation->payment?->is_invalid)->sum('amount');
        $status = ! $serial->purchaseTerm ? 'not_set' : ($paid <= 0 ? 'unpaid' : ($paid + 0.001 >= $price ? 'paid' : 'partial'));

        return view('livewire.finance-serial-status', compact('serial', 'price', 'paid', 'status'))
            ->layout('layouts.app', ['title'=>'Payment Status']);
    }
}
