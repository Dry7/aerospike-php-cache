<?php

namespace App\Repositories;

use App\Contracts\BalanceRepository;

class NullBalanceRepository implements BalanceRepository
{
    public function load(int $userID): ?float
    {
        return null;
    }

    public function save(int $userID, float $value): bool
    {
        return true;
    }

    public function transaction(\Closure $closure): bool
    {
        return $closure();
    }
}
