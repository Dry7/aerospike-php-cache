<?php

namespace App\Providers;

use App\Contracts\AerospikeClient;
use App\Contracts\BalanceRepository;
use App\Contracts\CacheRepository;
use App\Contracts\Locker;
use App\Repositories\AerospikeCacheRepository;
use App\Repositories\AerospikeLocker;
use App\Repositories\DBBalanceRepository;
use App\Services\AerospikeService;
use GuzzleHttp\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BalanceRepository::class, DBBalanceRepository::class);
        $this->app->bind(AerospikeClient::class, fn () => new AerospikeService(new Client([
            'base_uri' => 'http://aerospike-gateway:8080',
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 1,
        ])));
        $this->app->bind(
            Locker::class,
            fn (Application $app) => new AerospikeLocker($app->get(AerospikeClient::class), "cache", "balance:lock", 5),
        );
        $this->app->bind(
            CacheRepository::class,
            fn (Application $app) => new AerospikeCacheRepository($app->get(AerospikeClient::class), "cache", "balance"),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
