<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    public function homeEquipment(): JsonResponse
    {
        return $this->response(true, 'Home equipment retrieved.', Equipment::with('category')->where('status', 'Available')->where('available_quantity', '>', 0)->latest()->get());
    }

    public function index(): JsonResponse
    {
        return $this->response(true, 'Equipment retrieved.', Equipment::with('category')->latest()->paginate());
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['available_quantity'] ??= $data['total_quantity'];

        return $this->response(true, 'Equipment created.', Equipment::create($data), 201);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return $this->response(true, 'Equipment retrieved.', $equipment->load('category'));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): JsonResponse
    {
        $equipment->update($request->validated());

        return $this->response(true, 'Equipment updated.', $equipment);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return $this->response(true, 'Equipment deleted.');
    }
}
