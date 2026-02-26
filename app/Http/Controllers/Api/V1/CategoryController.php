<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\ListCategoriesAction;
use App\Actions\Categories\UpdateCategoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MessageResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index(ListCategoriesAction $action): AnonymousResourceCollection
    {
        return CategoryResource::collection($action->handle());
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        Gate::authorize('admin');

        $category = $action->handle($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): CategoryResource
    {
        Gate::authorize('admin');

        return new CategoryResource($action->handle($category, $request->validated()));
    }

    public function destroy(Category $category, DeleteCategoryAction $action): MessageResource
    {
        Gate::authorize('admin');

        $action->handle($category);

        return new MessageResource('Category deleted successfully.');
    }
}
