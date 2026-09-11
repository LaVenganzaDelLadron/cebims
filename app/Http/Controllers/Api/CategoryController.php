<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->response(true, 'Categories retrieved.', Category::withCount('equipment')->latest()->get());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        return $this->response(true, 'Category created.', Category::create($data), 201);
    }

    public function show(Category $category): JsonResponse
    {
        return $this->response(true, 'Category retrieved.', $category->load('equipment'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return $this->response(true, 'Category updated.', $category);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->response(true, 'Category deleted.');
    }
}
