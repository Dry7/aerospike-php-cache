<?php

namespace App\Repositories;

use App\Contracts\BalanceRepository;
use Illuminate\Support\Facades\DB;

class DBBalanceRepository implements BalanceRepository
{
    public function load(int $userID): float | null
    {
        return DB::table('user_balance')->where('id', $userID)->lockForUpdate()->first('balance')?->balance;
    }

    public function save(int $userID, float $value): bool {
        DB::table('user_balance')->upsert(['id' => $userID, 'balance' => $value, 'updated_at' => now()], ['id']);
        return true;
    }

    public function transaction(\Closure $closure): bool
    {
        return DB::transaction($closure);
    }
}
