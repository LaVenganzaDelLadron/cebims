<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BorrowRequest extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'barangay', 'purpose', 'borrow_date', 'expected_return_date', 'status', 'admin_notes'];

    protected function casts(): array
    {
        return ['borrow_date' => 'date', 'expected_return_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BorrowRequestItem::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
