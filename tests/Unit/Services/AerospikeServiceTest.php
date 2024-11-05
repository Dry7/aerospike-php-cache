<?php

namespace Tests\Unit\Services;

use App\Services\AerospikeService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Tests\TestCase;

class AerospikeServiceTest extends TestCase
{
    public function testGetWhenCacheExists(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('get')->with("/v1/kvs/$namespace/$set/$key")->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->get($namespace, $set, $key);

        self::assertEquals($price, $actual[0]);
        self::assertEquals($generation, $actual[1]);
    }

    public function testGetWhenNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = null;
        $generation = 0;

        $client = Mockery::mock(Client::class);
        $request = new Request('GET', '');
        $response = new Response(500);
        $client->shouldReceive('get')->with("/v1/kvs/$namespace/$set/$key")->andThrow(new ClientException('', $request, $response))->once();
        $service = new AerospikeService($client);
        $actual = $service->get($namespace, $set, $key);

        self::assertEquals($price, $actual[0]);
        self::assertEquals($generation, $actual[1]);
    }

    public function testGetWhenUnexpectedResponse(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = null;
        $generation = 0;

        $client = Mockery::mock(Client::class);
        $response = new Response;
        $client->shouldReceive('get')->with("/v1/kvs/$namespace/$set/$key")->andReturn($response)->once()->once();
        $service = new AerospikeService($client);
        $actual = $service->get($namespace, $set, $key);

        self::assertEquals($price, $actual[0]);
        self::assertEquals($generation, $actual[1]);
    }

    public function testGetFloatWhenCacheExists(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('get')->with("/v1/kvs/$namespace/$set/$key")->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->getFloat($namespace, $set, $key);

        self::assertEquals($price, $actual[0]);
        self::assertEquals($generation, $actual[1]);
    }

    public function testGetFloatWhenNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 0.00;
        $generation = 0;

        $client = Mockery::mock(Client::class);
        $request = new Request('GET', '');
        $response = new Response(500);
        $client->shouldReceive('get')->with("/v1/kvs/$namespace/$set/$key")->andThrow(new ClientException('', $request, $response))->once();
        $service = new AerospikeService($client);
        $actual = $service->getFloat($namespace, $set, $key);

        self::assertEquals($price, $actual[0]);
        self::assertEquals($generation, $actual[1]);
    }

    public function testPut(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('put')->with("/v1/kvs/$namespace/$set/$key", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->put($namespace, $set, $key, ['intBin' => $price]);

        self::assertTrue($actual);
    }

    public function testPutWithGeneration(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('put')->with("/v1/kvs/$namespace/$set/$key?generation=$generation", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->put($namespace, $set, $key, ['intBin' => $price], $generation);

        self::assertTrue($actual);
    }

    public function testPutWithNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(500);
        $client->shouldReceive('put')->with("/v1/kvs/$namespace/$set/$key?generation=$generation", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->put($namespace, $set, $key, ['intBin' => $price], $generation);

        self::assertFalse($actual);
    }

    public function testPutFloatWithGeneration(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('put')->with("/v1/kvs/$namespace/$set/$key?generation=$generation", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->putFloat($namespace, $set, $key, $price, $generation);

        self::assertTrue($actual);
    }

    public function testPutFloatWithNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(500);
        $client->shouldReceive('put')->with("/v1/kvs/$namespace/$set/$key?generation=$generation", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->putFloat($namespace, $set, $key, $price, $generation);

        self::assertFalse($actual);
    }

    public function testPost(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('post')->with("/v1/kvs/$namespace/$set/$key", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->post($namespace, $set, $key, ['intBin' => $price]);

        self::assertTrue($actual);
    }

    public function testPostWithExpiration(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;
        $expiration = 10;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('post')->with("/v1/kvs/$namespace/$set/$key?expiration=$expiration", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->post($namespace, $set, $key, ['intBin' => $price], $expiration);

        self::assertTrue($actual);
    }

    public function testPostWithNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;

        $client = Mockery::mock(Client::class);
        $response = new Response(500);
        $client->shouldReceive('post')->with("/v1/kvs/$namespace/$set/$key", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->post($namespace, $set, $key, ['intBin' => $price]);

        self::assertFalse($actual);
    }

    public function testPostFloat(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;
        $generation = 1;

        $client = Mockery::mock(Client::class);
        $response = new Response(200, [], json_encode(['generation' => $generation, 'bins' => ['intBin' => $price]]));
        $client->shouldReceive('post')->with("/v1/kvs/$namespace/$set/$key", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->postFloat($namespace, $set, $key, $price);

        self::assertTrue($actual);
    }

    public function testPostFloatWithNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $price = 99.99;

        $client = Mockery::mock(Client::class);
        $response = new Response(500);
        $client->shouldReceive('post')->with("/v1/kvs/$namespace/$set/$key", ['json' => ['intBin' => $price]])->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->postFloat($namespace, $set, $key, $price);

        self::assertFalse($actual);
    }

    public function testDelete(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';

        $client = Mockery::mock(Client::class);
        $response = new Response(200);
        $client->shouldReceive('delete')->with("/v1/kvs/$namespace/$set/$key")->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->delete($namespace, $set, $key);

        self::assertTrue($actual);
    }

    public function testDeleteWithFilter(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';
        $token = 'key';

        $client = Mockery::mock(Client::class);
        $response = new Response(200);
        $client->shouldReceive('delete')->with("/v1/kvs/$namespace/$set/$key".'?filterExp=AVEDc3RyQmluA2tleQ==')->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->delete($namespace, $set, $key, null, $token);

        self::assertTrue($actual);
    }

    public function testDeleteWithNetworkError(): void
    {
        $namespace = 'namespace';
        $set = 'set';
        $key = 'key';

        $client = Mockery::mock(Client::class);
        $response = new Response(500);
        $client->shouldReceive('delete')->with("/v1/kvs/$namespace/$set/$key")->andReturn($response)->once();
        $service = new AerospikeService($client);
        $actual = $service->delete($namespace, $set, $key);

        self::assertFalse($actual);
    }
}
