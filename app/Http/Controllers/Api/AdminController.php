<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BorrowRequest;
use App\Models\Equipment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function updateUser(Request $request, User $user, AuditLogService $auditLog): JsonResponse
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
        if (($data['role'] ?? $user->role) !== 'admin' && $user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return $this->response(false, 'The last administrator cannot be demoted.', null, 422);
        }

        DB::transaction(function () use ($user, $data, $auditLog): void {
            $user->update($data);
            $auditLog->record('admin.user.updated', $user, ['fields' => array_keys($data)]);
        });

        return $this->response(true, 'User updated.', $user->fresh());
    }

    public function destroyUser(User $user, AuditLogService $auditLog): JsonResponse
    {
        $this->authorize('delete', $user);
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return $this->response(false, 'The last administrator cannot be deleted.', null, 422);
        }
        DB::transaction(function () use ($user, $auditLog): void {
            $user->delete();
            $auditLog->record('admin.user.deleted', $user);
        });

        return $this->response(true, 'User deleted.');
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'action' => ['sometimes', 'string', 'max:100'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);
        $query = AuditLog::with('user:id,username')
            ->when(isset($filters['action']), fn ($query) => $query->where('action', $filters['action']))
            ->when(isset($filters['from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest()
            ->orderByDesc('id');

        return $this->response(true, 'Audit logs retrieved.', $query->paginate());
    }
}
