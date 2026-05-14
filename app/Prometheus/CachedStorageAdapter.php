<?php

namespace App\Prometheus;

use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\Storage\InMemory;
use Prometheus\Summary;
use Spatie\Prometheus\Adapters\LaravelCacheAdapter;

class CachedStorageAdapter extends LaravelCacheAdapter
{
    public function collect(bool $sortMetrics = true): array
    {
        $this->counters = $this->fetch(Counter::TYPE);
        $this->gauges = $this->fetch(Gauge::TYPE);
        $this->histograms = $this->fetch(Histogram::TYPE);
        $this->summaries = $this->fetch(Summary::TYPE);

        return InMemory::collect($sortMetrics);
    }
}
