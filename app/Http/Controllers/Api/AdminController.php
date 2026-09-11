<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BorrowRequest;
use App\Models\Equipment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return $this->response(true, 'Dashboard statistics retrieved.', [
            'users' => User::count(),
            'equipment' => Equipment::count(),
            'pending_borrow_requests' => BorrowRequest::where('status', 'Pending')->count(),
            'approved_borrow_requests' => BorrowRequest::where('status', 'Approved')->count(),
            'borrowed_borrow_requests' => BorrowRequest::where('status', 'Borrowed')->count(),
            'returned_borrow_requests' => BorrowRequest::where('status', 'Returned')->count(),
            'transactions' => Transaction::count(),
        ]);
    }

    public function users(): JsonResponse
    {
        return $this->response(true, 'Users retrieved.', User::latest()->paginate());
    }

    public function showUser(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->response(true, 'User retrieved.', $user->loadCount('borrowRequests'));
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'address' => ['sometimes', 'string'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:150', Rule::unique('users')->ignore($user)],
            'username' => ['sometimes', 'string', 'max:50', Rule::unique('users')->ignore($user)],
            'role' => ['sometimes', Rule::in(['admin', 'user'])],
        ]);

        $user->update($data);

        return $this->response(true, 'User updated.', $user->fresh());
    }

    public function destroyUser(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $user->delete();

        return $this->response(true, 'User deleted.');
    }
}
