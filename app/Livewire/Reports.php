<?php

namespace App\Livewire;

use App\Models\SerialPurchaseTerm;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Reports extends Component
{
    use WithPagination;

    public function boot(): void { abort_unless(auth()->user()?->isAdmin(), 403); }

    #[Url] public string $period = '30';
    #[Url] public string $startDate = '';
    #[Url] public string $endDate = '';
    #[Url] public string $supplierId = '';
    #[Url] public array $productIds = [];
    #[Url] public string $inventoryState = '';
    #[Url] public string $paymentStatus = '';
    public bool $showSupplierFilters = false;

    public function mount(): void { abort_unless(auth()->user()->isAdmin(), 403); }
    public function updatedPeriod(): void { $this->resetPage(); $this->resetPage('supplierPage'); }
    public function updatedSupplierId(): void { $this->productIds=[]; $this->resetPage('supplierPage'); }
    public function updatedProductIds(): void { $this->resetPage('supplierPage'); }
    public function updatedInventoryState(): void { $this->resetPage('supplierPage'); }
    public function updatedPaymentStatus(): void { $this->resetPage('supplierPage'); }
    public function openSupplierFilters(): void { $this->showSupplierFilters=true; }
    public function applySupplierFilters(): void
    {
        $this->validate([
            'supplierId'=>['nullable','exists:suppliers,id'], 'productIds'=>['array'], 'productIds.*'=>['integer','exists:products,id'],
            'inventoryState'=>['nullable','in:in_stock,with_reseller,sold,returned_to_supplier'],
            'paymentStatus'=>['nullable','in:unpaid,partial,paid'],
            'startDate'=>[$this->period==='custom'?'required':'nullable','date'],
            'endDate'=>[$this->period==='custom'?'required':'nullable','date','after_or_equal:startDate'],
        ]);
        $this->showSupplierFilters=false;
        $this->resetPage('supplierPage');
    }
    public function clearSupplierFilters(): void
    {
        $this->reset(['supplierId','productIds','inventoryState','paymentStatus','startDate','endDate']);
        $this->period='30';
        $this->showSupplierFilters=false;
        $this->resetPage('supplierPage');
    }
    public function applyCustomPeriod(): void
    {
        $this->validate(['startDate'=>['required','date'], 'endDate'=>['required','date','after_or_equal:startDate']]);
        $this->period = 'custom';
        $this->resetPage();
    }

    private function range(): array
    {
        $end = now()->endOfDay();
        if ($this->period === 'all') return [null, $end];
        if ($this->period === 'custom' && $this->startDate && $this->endDate) {
            try { return [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()]; } catch (\Throwable) {}
        }
        $days = in_array($this->period, ['1','7','30','90','365'], true) ? (int)$this->period : 30;
        return [$days === 1 ? today()->startOfDay() : today()->subDays($days - 1)->startOfDay(), $end];
    }

    private function ranged(Builder $query, string $column, ?Carbon $start, Carbon $end): Builder
    {
        return $query->when($start, fn ($q)=>$q->whereBetween($column, [$start, $end]));
    }

    private function chart(?Carbon $start, Carbon $end): array
    {
        $monthly = ! $start || $start->diffInDays($end) > 62;
        if ($monthly) {
            $chartStart = ($start && $start->greaterThan($end->copy()->subMonths(11))) ? $start->copy()->startOfMonth() : $end->copy()->subMonths(11)->startOfMonth();
            $sales = DB::table('orders')->whereBetween('created_at', [$chartStart, $end])->selectRaw("DATE_FORMAT(created_at, '%Y-%m') bucket, SUM(total) total")->groupBy('bucket')->pluck('total','bucket');
            $expenses = DB::table('expenses')->whereBetween('created_at', [$chartStart, $end])->selectRaw("DATE_FORMAT(created_at, '%Y-%m') bucket, SUM(amount) total")->groupBy('bucket')->pluck('total','bucket');
            $labels=[]; $salesData=[]; $expenseData=[];
            for ($cursor=$chartStart->copy(); $cursor->lte($end); $cursor->addMonth()) { $key=$cursor->format('Y-m'); $labels[]=$cursor->format('M y'); $salesData[]=(float)($sales[$key]??0); $expenseData[]=(float)($expenses[$key]??0); }
            return compact('labels','salesData','expenseData') + ['caption'=>'Monthly performance'];
        }
        $sales = DB::table('orders')->whereBetween('created_at', [$start, $end])->selectRaw('DATE(created_at) bucket, SUM(total) total')->groupBy('bucket')->pluck('total','bucket');
        $expenses = DB::table('expenses')->whereBetween('created_at', [$start, $end])->selectRaw('DATE(created_at) bucket, SUM(amount) total')->groupBy('bucket')->pluck('total','bucket');
        $labels=[]; $salesData=[]; $expenseData=[];
        for ($cursor=$start->copy(); $cursor->lte($end); $cursor->addDay()) { $key=$cursor->toDateString(); $labels[]=$cursor->format('d M'); $salesData[]=(float)($sales[$key]??0); $expenseData[]=(float)($expenses[$key]??0); }
        return compact('labels','salesData','expenseData') + ['caption'=>'Daily performance'];
    }

    private function records(?Carbon $start, Carbon $end)
    {
        $sales = $this->ranged(DB::table('orders')->where('is_invalid', false), 'created_at', $start, $end)
            ->selectRaw("created_at happened_at, 'sale' record_type, order_number reference, COALESCE(customer_name, 'Walk-in customer') description, total inflow, 0 outflow");
        $expenses = $this->ranged(DB::table('expenses')->where('is_invalid', false), 'created_at', $start, $end)
            ->selectRaw("created_at happened_at, 'expense' record_type, CONCAT('EXP-', id) reference, expense description, 0 inflow, amount outflow");
        $payments = $this->ranged(DB::table('supplier_payments')->where('supplier_payments.is_invalid', false)->join('suppliers','suppliers.id','=','supplier_payments.supplier_id'), 'supplier_payments.paid_at', $start, $end)
            ->selectRaw("supplier_payments.paid_at happened_at, 'supplier_payment' record_type, CONCAT('PAY-', supplier_payments.id) reference, CONCAT('Payment to ', suppliers.name) description, 0 inflow, supplier_payments.amount outflow");
        return DB::query()->fromSub($sales->unionAll($expenses)->unionAll($payments), 'financial_records')->orderByDesc('happened_at')->paginate(20);
    }

    private function supplierData(?Carbon $start, Carbon $end): array
    {
        if (! $this->supplierId) return ['stats'=>null,'records'=>null];
        $query=DB::table('serial_purchase_terms')
            ->join('product_serials','product_serials.id','=','serial_purchase_terms.product_serial_id')
            ->join('products','products.id','=','product_serials.product_id')
            ->leftJoin('supplier_payment_allocations','supplier_payment_allocations.product_serial_id','=','product_serials.id')
            ->leftJoin('supplier_payments','supplier_payments.id','=','supplier_payment_allocations.supplier_payment_id')
            ->where('serial_purchase_terms.supplier_id',$this->supplierId)
            ->when($this->productIds,fn($q)=>$q->whereIn('product_serials.product_id',$this->productIds))
            ->when($this->inventoryState,fn($q)=>$q->where('product_serials.status',$this->inventoryState))
            ->when($start,fn($q)=>$q->whereBetween('product_serials.created_at',[$start,$end]))
            ->selectRaw('product_serials.id, product_serials.serial, product_serials.status, products.name product_name, serial_purchase_terms.buying_price, COALESCE(SUM(CASE WHEN supplier_payments.is_invalid = 0 OR supplier_payments.id IS NULL THEN supplier_payment_allocations.amount ELSE 0 END),0) paid')
            ->groupBy('product_serials.id','product_serials.serial','product_serials.status','products.name','serial_purchase_terms.buying_price');
        $filtered=DB::query()->fromSub($query,'supplier_rows')
            ->when($this->paymentStatus==='unpaid',fn($q)=>$q->where('paid','<=',0))
            ->when($this->paymentStatus==='partial',fn($q)=>$q->where('paid','>',0)->whereColumn('paid','<','buying_price'))
            ->when($this->paymentStatus==='paid',fn($q)=>$q->whereColumn('paid','>=','buying_price'));
        $stats=DB::query()->fromSub(clone $filtered,'filtered_supplier_rows')->selectRaw("COUNT(*) total_units, COALESCE(SUM(buying_price),0) total_worth, COALESCE(SUM(LEAST(paid,buying_price)),0) total_paid, COALESCE(SUM(GREATEST(buying_price-paid,0)),0) total_unpaid, COALESCE(SUM(CASE WHEN status='in_stock' THEN buying_price ELSE 0 END),0) instock_worth, COALESCE(SUM(CASE WHEN status='with_reseller' THEN buying_price ELSE 0 END),0) takeout_worth")->first();
        return ['stats'=>$stats,'records'=>$filtered->orderBy('product_name')->orderBy('serial')->paginate(20,['*'],'supplierPage')];
    }

    public function render()
    {
        [$start,$end]=$this->range();
        $revenue=(float)$this->ranged(DB::table('orders')->where('is_invalid', false),'created_at',$start,$end)->sum('total');
        $expenses=(float)$this->ranged(DB::table('expenses')->where('is_invalid', false),'created_at',$start,$end)->sum('amount');
        $cogsQuery=DB::table('order_items')->join('orders','orders.id','=','order_items.order_id')->where('orders.is_invalid', false)->leftJoin('serial_purchase_terms','serial_purchase_terms.product_serial_id','=','order_items.product_serial_id');
        if ($start) $cogsQuery->whereBetween('orders.created_at',[$start,$end]);
        $soldBuyingPrice=(float)$cogsQuery->sum(DB::raw('COALESCE(serial_purchase_terms.buying_price, order_items.buying_price_snapshot, 0)'));
        $sources=$this->ranged(DB::table('orders')->where('is_invalid', false),'created_at',$start,$end)->selectRaw('customer_source, COUNT(*) orders_count, SUM(total) revenue')->groupBy('customer_source')->orderByDesc('orders_count')->get();

        $supplierData=$this->supplierData($start,$end);
        $supplierProducts=\App\Models\Product::whereHas('serials.purchaseTerm',fn($q)=>$q->when($this->supplierId,fn($x)=>$x->where('supplier_id',$this->supplierId)))->orderBy('name')->get();
        return view('livewire.reports', [
            'revenue'=>$revenue, 'expensesTotal'=>$expenses, 'soldBuyingPrice'=>$soldBuyingPrice, 'profit'=>$revenue-$soldBuyingPrice-$expenses,
            'chart'=>$this->chart($start,$end), 'sources'=>$sources, 'records'=>$this->records($start,$end),
            'suppliers'=>Supplier::orderBy('name')->get(), 'supplierProducts'=>$supplierProducts,
            'supplierStats'=>$supplierData['stats'], 'supplierRecords'=>$supplierData['records'],
            'periodLabel'=>$start ? $start->format('d M Y').' – '.$end->format('d M Y') : 'All time',
        ])->layout('layouts.app',['title'=>'Reports']);
    }
}
