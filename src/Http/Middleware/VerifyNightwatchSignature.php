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
        // A bare /webhooks/nightwatch uses the single shared secret; a
        // /webhooks/nightwatch/{source} path uses that source's own secret.
        $source = $request->route('source');

        if ($source === null) {
            $secret = config('raven.webhook.signing_secret');
            abort_if(blank($secret), 500, 'Raven webhook signing secret not configured.');
        } else {
            $secret = config('raven.webhook.sources.'.$source.'.secret');
            abort_if(blank($secret), 404, 'Unknown Nightwatch webhook source.');
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        $provided = (string) $request->header('Nightwatch-Signature', '');

        // Accept both a bare hex digest and a "sha256=" prefixed form, since
        // webhook providers differ on how they present the signature.
        if (str_starts_with($provided, 'sha256=')) {
            $provided = substr($provided, 7);
        }

        abort_unless(hash_equals($expected, $provided), 403, 'Invalid Nightwatch Signature.');

        return $next($request);
    }
}
