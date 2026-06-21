<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockReconciliationService
{
    public function mismatches(): array
    {
        return Product::query()->select(['id','name','stock'])
            ->withCount(['serials as calculated_stock'=>fn ($query)=>$query->where('status','in_stock')])
            ->get()->filter(fn ($product)=>(int)$product->stock !== (int)$product->calculated_stock)
            ->map(fn ($product)=>['id'=>$product->id,'name'=>$product->name,'stored'=>(int)$product->stock,'calculated'=>(int)$product->calculated_stock])
            ->values()->all();
    }

    public function repair(): int
    {
        $rows = $this->mismatches();
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) Product::whereKey($row['id'])->update(['stock'=>$row['calculated']]);
        });
        return count($rows);
    }
}
