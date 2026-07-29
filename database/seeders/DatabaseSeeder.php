<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BrandSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        $this->command?->info('Seeded: ' . implode(', ', [
            'brands (40)',
            'categories (7 parents / 33 children)',
            'products (20000)',
        ]));

        $this->command?->info('Run `php artisan products:reindex` next to sync into Elasticsearch.');
    }
}