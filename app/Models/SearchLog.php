<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $query
 * @property int $results_count
 * @property int|null $user_id
 * @property array|null $filters
 */
final class SearchLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['query', 'results_count', 'user_id', 'filters', 'created_at'];

    protected $casts = [
        'filters' => 'array',
        'created_at' => 'datetime',
    ];
}