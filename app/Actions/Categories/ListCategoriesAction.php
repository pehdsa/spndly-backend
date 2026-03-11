<?php

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCategoriesAction
{
    public function handle(): LengthAwarePaginator
    {
        return Category::query()->paginateFromRequest(orderBy: 'name', initialDirection: 'asc');
    }
}
