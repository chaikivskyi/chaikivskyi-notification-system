<?php

namespace App\Providers;

use App\Prometheus\CachedStorageAdapter;
use App\Support\Queues;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Spatie\Prometheus\Collectors\Queue\QueueSizeCollector;
use Spatie\Prometheus\Prometheus as PrometheusManager;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CollectorRegistry::class, function () {
            $store = config('prometheus.cache') ?? config('cache.default');

            $adapter = $store === null
                ? new InMemory
                : new CachedStorageAdapter(Cache::store($store));

            return new CollectorRegistry($adapter, false);
        });
    }

    public function boot(): void
    {
        App::make(PrometheusManager::class)->registerCollectorClasses([
            QueueSizeCollector::class,
        ], [
            'connection' => config('queue.default'),
            'queues' => [
                Queues::NOTIFICATIONS_HIGH,
                Queues::NOTIFICATIONS_NORMAL,
                Queues::NOTIFICATIONS_LOW,
            ],
        ]);
    }
}
