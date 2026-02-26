<?php

namespace App\Actions\Categories;

use App\Models\Category;

class CreateCategoryAction
{
    public function handle(array $data): Category
    {
        return Category::query()->create($data);
    }
}
