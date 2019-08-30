<?php

namespace MCordingley\LaraLockTests;

use Illuminate\Auth\GenericUser;
use MCordingley\LaraLock\LaraLock;
use MCordingley\LaraLock\ServiceProvider;
use Orchestra\Testbench\TestCase;

final class LaravelBindingsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    public function testFacadeRegistration()
    {
        config(['laralock.key' => 'base64:/dgzsXQlhjIRRMIu9mt/IU3N54a8njxKK1zvAUYzzbI=']);

        $user = new GenericUser(['id' => 1]);
        $user->password =  LaraLock::make('foo', $user);

        static::assertTrue(LaraLock::check('foo', $user));
    }
}
