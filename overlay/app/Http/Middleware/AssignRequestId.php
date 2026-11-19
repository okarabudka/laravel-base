<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tags every log entry of a request with a request id and returns it to the client.
 */
class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $this->incomingId($request) ?? (string) Str::uuid();

        // Context is added to every log entry and carried into queued jobs.
        Context::add('request_id', $id);

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }

    private function incomingId(Request $request): ?string
    {
        $id = $request->headers->get(self::HEADER);

        return is_string($id) && preg_match('/^[A-Za-z0-9._-]{1,128}$/', $id) === 1 ? $id : null;
    }
}
