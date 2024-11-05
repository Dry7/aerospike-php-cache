<?php

namespace App\Repositories;

use App\Contracts\AerospikeClient;
use App\Contracts\CacheRepository;

class AerospikeCacheRepository implements CacheRepository
{
    public function __construct(
        private readonly AerospikeClient $client,
        private readonly string $namespace,
        private readonly string $set,
    ) {}

    public function load(int $userID): array
    {
        return $this->client->getFloat($this->namespace, $this->set, $userID);
    }

    public function create(int $userID, float $value): bool
    {
        return $this->client->postFloat($this->namespace, $this->set, $userID, $value);
    }

    public function save(int $userID, float $value): bool
    {
        try {
            return $this->client->putFloat($this->namespace, $this->set, $userID, $value);
        } catch (\Exception) {
            return $this->client->postFloat($this->namespace, $this->set, $userID, $value);
        }
    }

    public function rollback(int $userID, float $value, ?int $version = null): bool
    {
        if ($version === 1) {
            return $this->client->delete($this->namespace, $this->set, $userID);
        }
        try {
            return $this->client->putFloat($this->namespace, $this->set, $userID, $value, $version);
        } catch (\Exception $exception) {
            return false;
        }
    }
}
