<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'description',
    ];

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
