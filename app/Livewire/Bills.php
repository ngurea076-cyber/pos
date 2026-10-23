<?php

namespace App\Livewire;

use App\Models\Bill;
use Livewire\Component;

class Bills extends Component
{
    public bool $showForm = false;
    public ?int $editing = null;
    public string $name = '';
    public string $category = '';
    public string $amount = '';
    public string $dueDay = '';
    public string $frequency = 'monthly';
    public string $notes = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(Bill $bill): void
    {
        $this->editing = $bill->id;
        $this->name = $bill->name;
        $this->category = $bill->category;
        $this->amount = (string) $bill->amount;
        $this->dueDay = $bill->due_day === null ? '' : (string) $bill->due_day;
        $this->frequency = $bill->frequency;
        $this->notes = $bill->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'dueDay' => ['nullable', 'integer', 'between:1,31'],
            'frequency' => ['required', 'in:weekly,monthly,quarterly,yearly,one_time'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = [
            'name' => trim($data['name']),
            'category' => trim($data['category']),
            'amount' => $data['amount'],
            'due_day' => $data['dueDay'] ?: null,
            'frequency' => $data['frequency'],
            'notes' => filled($data['notes']) ? trim($data['notes']) : null,
        ];

        if ($this->editing) {
            Bill::findOrFail($this->editing)->update($payload);
        } else {
            $payload['created_by'] = auth()->id();
            Bill::create($payload);
        }

        $wasEditing = (bool) $this->editing;
        $this->closeForm();
        session()->flash('status', $wasEditing ? 'Bill updated.' : 'Bill added.');
    }

    public function toggleActive(Bill $bill): void
    {
        $bill->update(['is_active' => ! $bill->is_active]);
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editing', 'name', 'category', 'amount', 'dueDay', 'notes']);
        $this->frequency = 'monthly';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.bills', [
            'bills' => Bill::orderBy('is_active', 'desc')->orderBy('due_day')->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Bills']);
    }
}
