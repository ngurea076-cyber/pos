<?php

namespace App\Livewire;

use App\Models\Reseller;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Resellers extends Component
{
    use WithPagination;

    #[Url] public string $search = '';
    public bool $showForm = false;
    public ?int $editing = null;
    public string $name = '', $phone = '', $email = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function create(): void { $this->resetForm(); $this->showForm=true; }
    public function edit(Reseller $reseller): void { $this->editing=$reseller->id; $this->name=$reseller->name; $this->phone=$reseller->phone??''; $this->email=$reseller->email??''; $this->resetValidation(); $this->showForm=true; }
    public function cancel(): void { $this->resetForm(); $this->showForm=false; }
    public function save(): void
    {
        $data=$this->validate(['name'=>'required|string|max:255','phone'=>'nullable|string|max:30','email'=>'nullable|email|max:255']);
        Reseller::updateOrCreate(['id'=>$this->editing], collect($data)->map(fn($v)=>$v===''?null:$v)->all());
        $this->cancel();
        session()->flash('status','Reseller saved.');
    }
    private function resetForm(): void { $this->reset(['editing','name','phone','email']); $this->resetValidation(); }
    public function render() { $q=Reseller::query()->orderBy('name')->when($this->search,fn($q)=>$q->where(fn($x)=>$x->where('name','like','%'.$this->search.'%')->orWhere('phone','like','%'.$this->search.'%')->orWhere('email','like','%'.$this->search.'%'))); return view('livewire.resellers',['resellers'=>$q->paginate(20)])->layout('layouts.app',['title'=>'Resellers']); }
}
