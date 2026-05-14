<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    public const HEADER = 'X-Correlation-ID';

    public const CONTEXT_KEY = 'correlation_id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->headers->get(self::HEADER);
        $id = Str::isUuid($incoming) ? $incoming : (string) Str::uuid();

        Context::add(self::CONTEXT_KEY, $id);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(self::HEADER, $id);

        return $response;
    }
}
