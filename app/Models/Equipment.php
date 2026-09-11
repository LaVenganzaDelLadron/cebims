<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    protected $fillable = [
        'category_id', 'equipment_name', 'description', 'total_quantity',
        'available_quantity', 'equipment_condition', 'storage_location', 'image', 'status',
    ];

    protected function casts(): array
    {
        return ['total_quantity' => 'integer', 'available_quantity' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function borrowRequestItems(): HasMany
    {
        return $this->hasMany(BorrowRequestItem::class);
    }

    public function returnLogs(): HasMany
    {
        return $this->hasMany(ReturnLog::class);
    }
}
