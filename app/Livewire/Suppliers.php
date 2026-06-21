<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Suppliers extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    public bool $showForm = false;
    public ?int $editing = null;
    public string $name = '', $phone = '', $email = '', $address = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function create(): void { $this->resetForm(); $this->showForm = true; }
    public function edit(Supplier $supplier): void { $this->editing=$supplier->id; $this->name=$supplier->name; $this->phone=$supplier->phone??''; $this->email=$supplier->email??''; $this->address=$supplier->address??''; $this->resetValidation(); $this->showForm=true; }
    public function cancel(): void { $this->resetForm(); $this->showForm=false; }
    public function save(): void
    {
        $data=$this->validate(['name'=>'required|string|max:255','phone'=>'nullable|string|max:30','email'=>'nullable|email|max:255','address'=>'nullable|string|max:500']);
        Supplier::updateOrCreate(['id'=>$this->editing], collect($data)->map(fn($v)=>$v===''?null:$v)->all());
        $this->cancel();
        session()->flash('status','Supplier saved.');
    }
    private function resetForm(): void { $this->reset(['editing','name','phone','email','address']); $this->resetValidation(); }
    public function render() { $q=Supplier::query()->orderBy('name')->when($this->search,fn($q)=>$q->where(fn($x)=>$x->where('name','like','%'.$this->search.'%')->orWhere('phone','like','%'.$this->search.'%')->orWhere('email','like','%'.$this->search.'%'))); return view('livewire.suppliers',['suppliers'=>$q->paginate(20)])->layout('layouts.app',['title'=>'Suppliers']); }
}
