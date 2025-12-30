<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('testing')) {
            throw new RuntimeException('Tests must run in testing environment.');
        }

        $appKey = (string) config('app.key');
        if (trim($appKey) === '') {
            throw new RuntimeException('APP_KEY must be set for tests.');
        }

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection === 'sqlite') {
            if ($database !== ':memory:' && ! str_ends_with($database, 'database.sqlite')) {
                throw new RuntimeException('SQLite tests must use :memory: or database.sqlite.');
            }
            return;
        }

        $allowed = ['spnc_test', 'spnc_testing'];
        $isSafeName = in_array($database, $allowed, true)
            || str_contains($database, '_test')
            || str_contains($database, '_testing');

        if (! $isSafeName) {
            throw new RuntimeException(
                'Unsafe database for tests: ' . $database . '. Configure a dedicated test DB.'
            );
        }
    }
}
