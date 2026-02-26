<?php

namespace App\Actions\Categories;

use App\Models\Category;

class CreateCategoryAction
{
    public function handle(array $data): Category
    {
        $existing = Category::query()
            ->onlyTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->first();

        if ($existing) {
            $existing->restore();
            $existing->update($data);

            return $existing->fresh();
        }

        return Category::query()->create($data);
    }
}
