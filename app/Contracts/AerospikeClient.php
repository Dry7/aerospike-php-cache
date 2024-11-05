<?php

namespace App\Contracts;

interface AerospikeClient
{
    public function get(string $namespace, $set, string | int $id): array;
    public function getFloat(string $namespace, $set, string | int $id): array;
    public function post(string $namespace, $set, string | int $id, array $value, int $expiration = null): bool;
    public function postFloat(string $namespace, $set, string | int $id, float $value): bool;
    public function put(string $namespace, $set, string | int $id, array $value, int | null $version = null): bool;
    public function putFloat(string $namespace, $set, string | int $id, float $value, int | null $version = null): bool;
    public function delete(string $namespace, $set, string | int $id): bool;
}
