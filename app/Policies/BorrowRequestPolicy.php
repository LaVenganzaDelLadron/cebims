<?php

namespace App\Policies;

use App\Models\BorrowRequest;
use App\Models\User;

class BorrowRequestPolicy
{
    public function view(User $user, BorrowRequest $borrowRequest): bool
    {
        return $user->role === 'admin' || $borrowRequest->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'user';
    }
}
