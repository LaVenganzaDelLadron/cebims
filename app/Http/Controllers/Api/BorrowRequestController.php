<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnRequest;
use App\Http\Requests\StoreBorrowRequestRequest;
use App\Http\Requests\UpdateBorrowStatusRequest;
use App\Models\BorrowRequest;
use App\Models\Equipment;
use App\Models\ReturnLog;
use App\Models\Transaction;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowRequestController extends Controller
{
    public function index(): JsonResponse
    {
        $query = BorrowRequest::with(['user', 'items.equipment', 'transaction'])->latest();
        if (auth()->user()->role !== 'admin') {
            $query->whereBelongsTo(auth()->user());
        }

        return $this->response(true, 'Borrow requests retrieved.', $query->paginate());
    }

    public function myBorrowings(): JsonResponse
    {
        return $this->index();
    }

    public function store(StoreBorrowRequestRequest $request): JsonResponse
    {
        $this->authorize('create', BorrowRequest::class);
        $borrow = DB::transaction(function () use ($request): BorrowRequest {
            $borrow = BorrowRequest::create(array_merge($request->safe()->except('items')->toArray(), ['user_id' => $request->user()->id]));
            foreach ($request->validated('items') as $item) {
                $borrow->items()->create($item);
            }

            return $borrow->load('items.equipment');
        });

        return $this->response(true, 'Borrow request submitted.', $borrow, 201);
    }

    public function show(BorrowRequest $borrowRequest): JsonResponse
    {
        $this->authorize('view', $borrowRequest);

        return $this->response(true, 'Borrow request retrieved.', $borrowRequest->load(['user', 'items.equipment', 'transaction.returnLogs']));
    }

    public function approve(UpdateBorrowStatusRequest $request, BorrowRequest $borrowRequest, AuditLogService $auditLog): JsonResponse
    {
        $transaction = DB::transaction(function () use ($borrowRequest, $auditLog): Transaction {
            $borrowRequest = BorrowRequest::whereKey($borrowRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($borrowRequest->status === 'Pending', 422, 'Only pending requests can be approved.');
            $borrowRequest->load('items');
            foreach ($borrowRequest->items->groupBy('equipment_id') as $items) {
                $item = $items->first();
                $requestedQuantity = $items->sum('quantity');
                $equipment = Equipment::whereKey($item->equipment_id)->lockForUpdate()->firstOrFail();
                if ($equipment->available_quantity < $requestedQuantity) {
                    throw ValidationException::withMessages(['items' => "Insufficient stock for {$equipment->equipment_name}."]);
                }
                $equipment->decrement('available_quantity', $requestedQuantity);
                $equipment->refresh()->update(['status' => $equipment->available_quantity > 0 ? 'Available' : 'Unavailable']);
            }
            $borrowRequest->update(['status' => 'Approved']);

            $transaction = Transaction::create(['borrow_request_id' => $borrowRequest->id, 'approved_by' => auth()->id(), 'approved_at' => now()]);
            $auditLog->record('admin.borrow_request.approved', $borrowRequest, ['transaction_id' => $transaction->id]);

            return $transaction;
        });

        return $this->response(true, 'Borrow request approved.', $transaction->load('borrowRequest'));
    }

    public function reject(UpdateBorrowStatusRequest $request, BorrowRequest $borrowRequest, AuditLogService $auditLog): JsonResponse
    {
        abort_unless($borrowRequest->status === 'Pending', 422, 'Only pending requests can be rejected.');
        DB::transaction(function () use ($request, $borrowRequest, $auditLog): void {
            $borrowRequest->update([
                'status' => 'Rejected',
                'admin_notes' => $request->validated('admin_notes'),
            ]);
            $auditLog->record('admin.borrow_request.rejected', $borrowRequest, ['admin_notes' => $request->validated('admin_notes')]);
        });

        return $this->response(true, 'Borrow request rejected.', $borrowRequest);
    }

    public function release(UpdateBorrowStatusRequest $request, BorrowRequest $borrowRequest): JsonResponse
    {
        $transaction = $borrowRequest->transaction;
        abort_unless($transaction && $borrowRequest->status === 'Approved', 422, 'Only approved requests can be released.');
        $transaction->update(['released_at' => now()]);
        $borrowRequest->update(['status' => 'Borrowed']);

        return $this->response(true, 'Equipment released.', $transaction->fresh());
    }

    public function processReturn(ReturnRequest $request, BorrowRequest $borrowRequest, AuditLogService $auditLog): JsonResponse
    {
        $transaction = DB::transaction(function () use ($request, $borrowRequest, $auditLog): Transaction {
            $borrowRequest = BorrowRequest::whereKey($borrowRequest->id)->lockForUpdate()->firstOrFail();
            $transaction = $borrowRequest->transaction()->lockForUpdate()->first();
            abort_unless($transaction && $borrowRequest->status === 'Borrowed', 422, 'Only borrowed requests can be returned.');
            foreach (collect($request->validated('items'))->groupBy('equipment_id') as $items) {
                $item = $items->first();
                $returnedQuantity = $items->sum('quantity_returned');
                $equipment = Equipment::whereKey($item['equipment_id'])->lockForUpdate()->firstOrFail();
                $borrowed = $borrowRequest->items()->where('equipment_id', $equipment->id)->value('quantity') ?? 0;
                $alreadyReturned = ReturnLog::where('transaction_id', $transaction->id)
                    ->where('equipment_id', $equipment->id)
                    ->sum('quantity_returned');
                if ($returnedQuantity + $alreadyReturned > $borrowed) {
                    throw ValidationException::withMessages(['items' => 'Returned quantity exceeds borrowed quantity.']);
                }
                if ($item['item_condition'] !== 'Lost') {
                    $equipment->increment('available_quantity', $returnedQuantity);
                }
                $equipment->refresh()->update(['status' => $equipment->available_quantity > 0 ? 'Available' : 'Unavailable']);
                ReturnLog::create(array_merge($item, ['quantity_returned' => $returnedQuantity, 'transaction_id' => $transaction->id]));
            }
            $transaction->update(['returned_at' => now(), 'return_condition' => $request->validated('items.0.item_condition'), 'remarks' => $request->validated('remarks')]);
            $borrowRequest->update(['status' => 'Returned']);
            $auditLog->record('admin.transaction.returned', $transaction, [
                'borrow_request_id' => $borrowRequest->id,
                'items' => $request->validated('items'),
            ]);

            return $transaction;
        });

        return $this->response(true, 'Equipment return recorded.', $transaction->fresh()->load('returnLogs'));
    }
}
