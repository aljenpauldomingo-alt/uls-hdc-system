<?php

namespace Tests;

use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    /**
     * Response for the breached-password API (Have I Been Pwned). Null = "not breached".
     */
    protected ?Closure $pwnedPasswords = null;

    /**
     * Runs inside parent::setUp(), before RefreshDatabase migrates, so a misconfigured
     * environment can never wipe a real database.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            fwrite(STDERR, "\nTests aborted: they must use in-memory SQLite, but the connection is [{$connection}] with database [{$database}].\nCheck phpunit.xml and your shell environment.\n");

            exit(1);
        }

        return parent::setUpTraits();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Tests never call the internet.
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'api.pwnedpasswords.com')) {
                return $this->pwnedPasswords ? ($this->pwnedPasswords)($request) : Http::response('', 200);
            }

            return null;
        });
    }
}
