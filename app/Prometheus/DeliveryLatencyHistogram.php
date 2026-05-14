<?php

namespace App\Prometheus;

use Prometheus\CollectorRegistry;
use Prometheus\Histogram;

class DeliveryLatencyHistogram
{
    public const NAMESPACE = '';

    public const NAME = 'notification_delivery_latency_seconds';

    /**
     * @var list<float>
     */
    public const BUCKETS = [0.5, 1.0, 2.0, 5.0, 10.0, 30.0, 60.0, 300.0];

    public function __construct(private readonly CollectorRegistry $registry) {}

    public function observe(float $latencySeconds): void
    {
        $this->histogram()->observe($latencySeconds);
    }

    private function histogram(): Histogram
    {
        return $this->registry->getOrRegisterHistogram(
            self::NAMESPACE,
            self::NAME,
            'Queued-to-delivered latency in seconds',
            [],
            self::BUCKETS,
        );
    }
}
