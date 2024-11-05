<?php

namespace App\Repositories;

use App\Contracts\AerospikeClient;
use App\Contracts\CacheRepository;
use App\Contracts\Locker;

class AerospikeLocker implements Locker
{
    public function __construct(
        private readonly AerospikeClient $client,
        private readonly string $namespace,
        private readonly string $set,
        private readonly int $ttl,
    )
    {
    }

    public function acquire(int $userID, string $token): bool
    {
        return $this->client->post($this->namespace, $this->set, $userID, [
            'strBin' => $token,
        ], $this->ttl);
    }

    public function release(int $userID, string $token): bool
    {
        return $this->client->delete($this->namespace, $this->set, $userID, null, $token);
    }
}
