<?php

namespace App\Contracts;

interface BalanceRepository
{
    public function load(int $userID): ?float;

    public function save(int $userID, float $value): bool;

    public function transaction(\Closure $closure): bool;
}
