<?php

namespace App\Actions\Webhooks;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class ListWebhookCategoriesAction
{
    public function handle(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }
}
