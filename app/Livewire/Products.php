<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination;
    #[Url] public string $search = '';
    public ?int $editing = null;
    public bool $showForm = false;
    public string $name = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function save(): void {
        $data = $this->validate(['name'=>'required|max:255']);
        Product::updateOrCreate(['id'=>$this->editing], ['name' => $data['name']]);
        $this->reset(['editing','name']);
        $this->showForm = false;
        session()->flash('status', 'Product saved.');
    }
    public function create(): void { $this->reset(['editing','name']); $this->resetValidation(); $this->showForm=true; }
    public function edit(Product $product): void { $this->editing=$product->id; $this->name=$product->name; $this->resetValidation(); $this->showForm=true; }
    public function cancelEdit(): void { $this->reset(['editing','name']); $this->resetValidation(); $this->showForm=false; }
    public function render() { $q=Product::query()->orderBy('name'); if($this->search) $q->where('name','like','%'.$this->search.'%'); return view('livewire.products',['products'=>$q->paginate(20)])->layout('layouts.app',['title'=>'Products']); }
}
