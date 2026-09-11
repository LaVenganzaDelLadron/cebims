<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->response(true, 'Categories retrieved.', Category::withCount('equipment')->latest()->get());
    }

    public function store(StoreCategoryRequest $request, AuditLogService $auditLog): JsonResponse
    {
        $data = $request->validated();

        $category = DB::transaction(function () use ($data, $auditLog): Category {
            $category = Category::create($data);
            $auditLog->record('admin.category.created', $category, ['fields' => array_keys($data)]);

            return $category;
        });

        return $this->response(true, 'Category created.', $category, 201);
    }

    public function show(Category $category): JsonResponse
    {
        return $this->response(true, 'Category retrieved.', $category->load('equipment'));
    }

    public function update(UpdateCategoryRequest $request, Category $category, AuditLogService $auditLog): JsonResponse
    {
        DB::transaction(function () use ($request, $category, $auditLog): void {
            $category->update($request->validated());
            $auditLog->record('admin.category.updated', $category, ['fields' => array_keys($request->validated())]);
        });

        return $this->response(true, 'Category updated.', $category);
    }

    public function destroy(Category $category, AuditLogService $auditLog): JsonResponse
    {
        DB::transaction(function () use ($category, $auditLog): void {
            $category->delete();
            $auditLog->record('admin.category.deleted', $category);
        });

        return $this->response(true, 'Category deleted.');
    }
}
