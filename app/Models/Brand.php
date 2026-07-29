<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $logo_url
 */
final class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo_url'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}