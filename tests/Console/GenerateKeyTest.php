<?php

namespace MCordingley\LaraLockTests\Console;

use MCordingley\LaraLock\ServiceProvider;
use Orchestra\Testbench\TestCase;

final class GenerateKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists($this->app->environmentFilePath())) {
            touch($this->app->environmentFilePath());
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->app->environmentFilePath())) {
            unlink($this->app->environmentFilePath());
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    public function testGenerate()
    {
        $this->artisan('laralock:key:generate --show')->execute();

        static::assertNull(config('laralock.key'));

        $this->artisan('laralock:key:generate')->execute();

        static::assertNotNull(config('laralock.key'));
        static::assertStringContainsString(config('laralock.key'), file_get_contents($this->app->environmentFilePath()));
    }
}
