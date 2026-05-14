<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VerifyMailpitWebhookCredentials
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = config('services.mailpit.webhook.user');
        $password = config('services.mailpit.webhook.password');

        if (! is_string($user) || $user === '' || ! is_string($password) || $password === '') {
            abort(500, 'Mailpit webhook credentials are not configured.');
        }

        $providedUser = (string) $request->getUser();
        $providedPassword = (string) $request->getPassword();

        if (! hash_equals($user, $providedUser) || ! hash_equals($password, $providedPassword)) {
            return response('Unauthorized', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Basic realm="mailpit-webhook"',
            ]);
        }

        return $next($request);
    }
}
