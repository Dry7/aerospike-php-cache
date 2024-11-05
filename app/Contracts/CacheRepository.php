<?php

namespace App\Contracts;

interface CacheRepository
{
    public function load(int $userID): array;

    public function create(int $userID, float $value): bool;

    public function save(int $userID, float $value): bool;

    public function rollback(int $userID, float $value, ?int $version = null): bool;
}
