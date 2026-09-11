<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['borrow_request_id', 'approved_by', 'approved_at', 'released_at', 'returned_at', 'return_condition', 'remarks'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime', 'released_at' => 'datetime', 'returned_at' => 'datetime'];
    }

    public function borrowRequest(): BelongsTo
    {
        return $this->belongsTo(BorrowRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returnLogs(): HasMany
    {
        return $this->hasMany(ReturnLog::class);
    }
}
