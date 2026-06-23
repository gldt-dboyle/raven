<?php

declare(strict_types=1);

namespace Gldt\Raven\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyNightwatchSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('raven.webhook.signing_secret');

        abort_if(blank($secret), 500, 'Raven webhook signing secret not configured.');

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $provided = (string) $request->header('Nightwatch-Signature', '');

        abort_unless(hash_equals($expected, $provided), 403, 'Invalid Nightwatch Signature.');

        return $next($request);
    }
}
