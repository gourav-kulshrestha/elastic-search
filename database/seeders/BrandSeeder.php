<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

final class BrandSeeder extends Seeder
{
    public function run(): void
    {
        Brand::factory()->count(40)->create();
    }
}