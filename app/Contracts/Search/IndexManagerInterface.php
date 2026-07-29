<?php

declare(strict_types=1);

namespace App\Contracts\Search;

interface IndexManagerInterface
{
    /**
     * Create a new physical index with mappings/settings and return its name.
     */
    public function createIndex(): string;

    /**
     * Check whether the write alias currently points to any index.
     */
    public function aliasExists(): bool;

    /**
     * Atomically swap the alias to point to the given index, removing it from any others.
     */
    public function switchAlias(string $newIndex): void;

    /**
     * Resolve the concrete index name(s) the alias currently points to.
     *
     * @return array<int, string>
     */
    public function resolveIndices(): array;

    /**
     * Delete a physical index by name.
     */
    public function deleteIndex(string $index): void;

    /**
     * The alias name used for read/write operations.
     */
    public function getAlias(): string;

    /**
     * Get the mapping definition for the index.
     */
    public function getMapping(): array;
}