<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function store(StoreEquipmentRequest $request, AuditLogService $auditLog): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('equipment', 'public');
        }
        $data['available_quantity'] ??= $data['total_quantity'];

        $equipment = DB::transaction(function () use ($data, $auditLog): Equipment {
            $equipment = Equipment::create($data);
            $auditLog->record('admin.equipment.created', $equipment, ['fields' => array_keys($data)]);

            return $equipment;
        });

        return $this->response(true, 'Equipment created.', $equipment, 201);
    }

    public function show(Equipment $equipment): JsonResponse
    {
        return $this->response(true, 'Equipment retrieved.', $equipment->load('category'));
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment, AuditLogService $auditLog): JsonResponse
    {
        $oldImage = $equipment->image;
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('equipment', 'public');
        }
        DB::transaction(function () use ($data, $equipment, $auditLog): void {
            $equipment->update($data);
            $auditLog->record('admin.equipment.updated', $equipment, ['fields' => array_keys($data)]);
        });
        if (isset($data['image']) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $this->response(true, 'Equipment updated.', $equipment);
    }

    public function destroy(Equipment $equipment, AuditLogService $auditLog): JsonResponse
    {
        DB::transaction(function () use ($equipment, $auditLog): void {
            $equipment->delete();
            $auditLog->record('admin.equipment.deleted', $equipment);
        });

        return $this->response(true, 'Equipment deleted.');
    }
}
