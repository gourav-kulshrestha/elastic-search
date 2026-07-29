<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SearchSuggestionClick extends Model
{
    public $timestamps = false;

    protected $fillable = ['query', 'product_id', 'user_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}