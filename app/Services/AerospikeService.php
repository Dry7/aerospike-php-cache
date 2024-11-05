<?php

namespace App\Services;

use App\Contracts\AerospikeClient;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

class AerospikeService implements AerospikeClient
{
    public function __construct(private readonly Client $client) {}

    public function get(string $namespace, $set, string|int $id): array
    {
        try {
            $response = json_decode($this->client->get("/v1/kvs/$namespace/$set/$id")->getBody()->getContents());

            return [$response->bins->intBin, $response->generation];
        } catch (\Exception) {
            return [null, 0];
        }
    }

    public function getFloat(string $namespace, $set, int|string $id): array
    {
        $value = $this->get($namespace, $set, $id);

        if ($value[0] === null) {
            return [null, 0];
        }

        return [(float) $value[0], $value[1] ?? 0];
    }

    public function put(string $namespace, $set, string|int $id, array $value, ?int $version = null): bool
    {
        return $this->client->put(
            "/v1/kvs/$namespace/$set/$id".($version ? '?generation='.$version : ''),
            ['json' => $value],
        )->getStatusCode() === Response::HTTP_OK;
    }

    public function putFloat(string $namespace, $set, int|string $id, float $value, ?int $version = null): bool
    {
        return $this->put($namespace, $set, $id, [
            'intBin' => $value,
        ], $version);
    }

    public function post(string $namespace, $set, string|int $id, array $value, ?int $expiration = null): bool
    {
        return $this->client->post(
            "/v1/kvs/$namespace/$set/$id".($expiration ? '?expiration='.$expiration : ''),
            ['json' => $value],
        )->getStatusCode() === Response::HTTP_OK;
    }

    public function postFloat(string $namespace, $set, int|string $id, float $value): bool
    {
        return $this->post($namespace, $set, $id, [
            'intBin' => $value,
        ]);
    }

    public function delete(string $namespace, $set, string|int $id, ?int $version = null, ?string $filterExp = null): bool
    {
        return $this->client->delete(
            "/v1/kvs/$namespace/$set/$id".($filterExp ? '?filterExp='.$this->filterExp($filterExp) : ''),
        )->getStatusCode() === Response::HTTP_OK;
    }

    // docs: https://github.com/aerospike/aerospike-rest-gateway/blob/master/docs/expressions.md
    private function filterExp(string $token): string
    {
        return base64_encode("QstrBin$token");
    }
}
