<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnLog extends Model
{
    use HasFactory;

    protected $fillable = ['transaction_id', 'equipment_id', 'quantity_returned', 'item_condition', 'remarks'];

    protected function casts(): array
    {
        return ['quantity_returned' => 'integer'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
