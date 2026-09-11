<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnEquipmentRequest;
use App\Models\BorrowRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function borrowedItems(): JsonResponse
    {
        return $this->response(true, 'Borrowed items retrieved.', BorrowRequest::with(['user', 'items.equipment', 'transaction'])->where('status', 'Borrowed')->latest()->paginate());
    }

    public function returnEquipment(ReturnEquipmentRequest $request, Transaction $transaction): JsonResponse
    {
        return app(BorrowRequestController::class)->processReturn($request, $transaction->borrowRequest);
    }

    public function history(): JsonResponse
    {
        return $this->response(true, 'Transaction history retrieved.', Transaction::with(['borrowRequest.user', 'borrowRequest.items.equipment', 'returnLogs'])->latest()->paginate());
    }
}
