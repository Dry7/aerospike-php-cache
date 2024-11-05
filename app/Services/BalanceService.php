<?php

namespace App\Services;

use App\Contracts\BalanceRepository;
use App\Contracts\CacheRepository;
use App\Contracts\Locker;
use Illuminate\Support\Carbon;

class BalanceService
{
    public function __construct(
        private readonly BalanceRepository $repository,
        private readonly CacheRepository $cache,
        private readonly Locker $locker,
        private readonly int $lockTtl = 5
    ) {}

    public function get(int $userID): ?float
    {
        $balance = $this->cache->load($userID);
        if ($balance[0] !== null) {
            return $balance[0];
        }
        $token = uuid_create();
        $this->locker->acquire($userID, $token);
        $value = (float) $this->repository->load($userID);
        $this->cache->create($userID, $value);
        $this->locker->release($userID, $token);

        return $value;
    }

    public function save(int $userID, float $value): bool
    {
        [$balance, $version] = $this->cache->load($userID);
        if ($balance !== null && $this->isSame($balance, $value)) {
            return true;
        }

        $token = null;
        try {
            $this->repository->transaction(function () use ($userID, $value, &$token) {
                $start = now();
                $token = uuid_create();
                $this->locker->acquire($userID, $token);
                $this->repository->save($userID, $value);
                $this->checkTimer($start);
                $this->cache->save($userID, $value);
                $this->checkTimer($start);

                return true;
            });
            $this->locker->release($userID, $token);

            return true;
        } catch (\Exception) {
            if ($version > 0) {
                $this->cache->rollback($userID, $balance, $version);
            }
            if ($token !== null) {
                $this->locker->release($userID, $token);
            }

            return false;
        }
    }

    private function checkTimer(Carbon $start): void
    {
        if ($start->diffInSeconds(now()) >= $this->lockTtl) {
            throw new LockExpiredException;
        }
    }

    private function isSame(float $left, float $right): bool
    {
        return $this->toString($left) === $this->toString($right);
    }

    private function toString(float $value): string
    {
        return (string) round($value, 2);
    }
}
