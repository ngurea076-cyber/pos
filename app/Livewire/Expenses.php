<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Services\RecordCorrectionService;
use Livewire\Component;
use Livewire\WithPagination;

class Expenses extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public string $expense = '';
    public string $category = '';
    public string $amount = '';
    public string $notes = '';
    public ?int $editing = null;
    public string $correctionReason = '';
    public bool $showInvalidationForm = false;
    public ?int $invalidating = null;

    public function create(): void
    {
        $this->reset(['expense', 'category', 'amount', 'notes', 'editing', 'correctionReason']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->reset(['expense', 'category', 'amount', 'notes', 'editing', 'correctionReason']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'expense' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:food,utilities,fare'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = [
            'expense' => trim($data['expense']),
            'category' => trim($data['category']),
            'amount' => $data['amount'],
            'notes' => filled($data['notes']) ? trim($data['notes']) : null,
            'created_by' => auth()->id(),
        ];
        if ($this->editing) {
            $reason = $this->validate(['correctionReason'=>['required','string','max:500']])['correctionReason'];
            $expense = Expense::findOrFail($this->editing);
            unset($payload['created_by']);
            app(RecordCorrectionService::class)->edit($expense, $payload, trim($reason));
        } else {
            Expense::create($payload);
        }

        $this->closeForm();
        $this->resetPage();
        session()->flash('status', $this->editing ? 'Expense corrected successfully.' : 'Expense recorded successfully.');
    }

    public function edit(Expense $expense): void
    {
        app(RecordCorrectionService::class)->assertEditable($expense);
        $this->editing=$expense->id; $this->expense=$expense->expense; $this->category=$expense->category; $this->amount=(string)$expense->amount; $this->notes=$expense->notes??''; $this->correctionReason=''; $this->showForm=true;
    }

    public function confirmInvalidate(Expense $expense): void
    {
        abort_unless(auth()->user()?->isAdmin(),403);
        abort_if($expense->is_invalid,422,'This expense is already invalidated.');
        $this->invalidating=$expense->id; $this->correctionReason=''; $this->showInvalidationForm=true;
    }

    public function invalidate(): void
    {
        abort_unless(auth()->user()?->isAdmin(),403);
        $data=$this->validate(['correctionReason'=>['required','string','max:500']]);
        app(RecordCorrectionService::class)->invalidate(Expense::findOrFail($this->invalidating),trim($data['correctionReason']));
        $this->showInvalidationForm=false; $this->invalidating=null; $this->correctionReason='';
        session()->flash('status','Expense deleted/invalidated.');
    }

    public function render()
    {
        return view('livewire.expenses', [
            'expenses' => Expense::with('user')->latest()->paginate(20),
        ])->layout('layouts.app', ['title' => 'Expenses']);
    }
}
