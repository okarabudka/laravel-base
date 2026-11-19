<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

/**
 * Readiness check: 200 when the database, cache and queue respond, 503 otherwise.
 * `/up` stays the plain liveness check.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(function () {
                $key = 'health:'.Str::random(8);
                Cache::put($key, true, 10);
                $ok = Cache::pull($key) === true;
                throw_unless($ok, new \RuntimeException('cache read-back failed'));
            }),
            'queue' => $this->check(fn () => Queue::size()),
        ];

        $healthy = ! in_array('fail', $checks, true);

        return response()->json(
            ['status' => $healthy ? 'ok' : 'fail', 'checks' => $checks],
            $healthy ? 200 : 503,
        );
    }

    /**
     * @param  callable(): mixed  $probe
     */
    private function check(callable $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (Throwable $e) {
            report($e);

            return 'fail';
        }
    }
}
