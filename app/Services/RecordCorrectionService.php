<?php

namespace App\Services;

use App\Models\RecordCorrection;
use Illuminate\Database\Eloquent\Model;

class RecordCorrectionService
{
    public function assertEditable(Model $record): void
    {
        abort_if($record->is_invalid, 422, 'Invalidated records cannot be edited.');
        $limit = auth()->user()?->isAdmin() ? 3 : 2;
        abort_if($record->edit_count >= $limit, 422, "This record has reached its {$limit}-edit limit.");
    }

    public function edit(Model $record, array $changes, string $reason): void
    {
        $this->assertEditable($record);
        $before = $record->attributesToArray();
        $record->fill($changes);
        $record->edit_count++;
        $record->save();
        RecordCorrection::create([
            'record_type'=>$record->getMorphClass(), 'record_id'=>$record->getKey(), 'action'=>'edited',
            'before_data'=>$before, 'after_data'=>$record->fresh()->attributesToArray(),
            'reason'=>$reason, 'user_id'=>auth()->id(),
        ]);
    }

    public function invalidate(Model $record, string $reason): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_if($record->is_invalid, 422, 'This record is already invalidated.');
        $before = $record->attributesToArray();
        $record->update(['is_invalid'=>true, 'invalid_reason'=>$reason, 'invalidated_by'=>auth()->id(), 'invalidated_at'=>now()]);
        RecordCorrection::create([
            'record_type'=>$record->getMorphClass(), 'record_id'=>$record->getKey(), 'action'=>'invalidated',
            'before_data'=>$before, 'after_data'=>$record->fresh()->attributesToArray(),
            'reason'=>$reason, 'user_id'=>auth()->id(),
        ]);
    }
}
