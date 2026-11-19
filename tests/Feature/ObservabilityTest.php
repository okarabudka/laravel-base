<?php

use App\Http\Middleware\AssignRequestId;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\withHeader;

test('every response carries a generated request id', function () {
    $id = get('/up')->assertOk()->headers->get(AssignRequestId::HEADER);

    expect($id)->toBeString()->and(Str::isUuid((string) $id))->toBeTrue();
});

test('a valid incoming request id is kept and added to the log context', function () {
    withHeader(AssignRequestId::HEADER, 'abc-123')->get('/up')
        ->assertHeader(AssignRequestId::HEADER, 'abc-123');

    expect(Context::get('request_id'))->toBe('abc-123');
});

test('an invalid incoming request id is replaced', function () {
    $id = withHeader(AssignRequestId::HEADER, "bad id\n")->get('/up')->headers->get(AssignRequestId::HEADER);

    expect(Str::isUuid((string) $id))->toBeTrue();
});

test('health reports database, cache and queue', function () {
    getJson('/health')->assertOk()->assertExactJson([
        'status' => 'ok',
        'checks' => ['database' => 'ok', 'cache' => 'ok', 'queue' => 'ok'],
    ]);
});

test('health fails with 503 when the database is down', function () {
    config(['database.connections.sqlite.database' => '/nonexistent/database.sqlite']);
    DB::purge('sqlite');

    getJson('/health')->assertStatus(503)->assertJsonPath('checks.database', 'fail')->assertJsonPath('status', 'fail');
});
