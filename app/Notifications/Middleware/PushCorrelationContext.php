<?php

namespace App\Notifications\Middleware;

use App\Http\Middleware\CorrelationId;
use Closure;
use Illuminate\Support\Facades\Context;

class PushCorrelationContext
{
    public function __construct(private readonly ?string $correlationId) {}

    public function handle(object $job, Closure $next): mixed
    {
        if ($this->correlationId) {
            Context::add(CorrelationId::CONTEXT_KEY, $this->correlationId);
        }

        return $next($job);
    }
}
