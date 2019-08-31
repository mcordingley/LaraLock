<?php

namespace MCordingley\LaraLockTests;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use MCordingley\LaraLock\Auth\LockedUserProvider;
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

    public function testProviderRegistration()
    {
        config([
            'auth' => [
                'providers' => [
                    'users' => [
                        'driver' => 'laralock',
                        'model' => 'foo',
                    ],
                ],
            ],
            'laralock.key' => 'base64:/dgzsXQlhjIRRMIu9mt/IU3N54a8njxKK1zvAUYzzbI=',
        ]);

        static::assertInstanceOf(LockedUserProvider::class, Auth::createUserProvider('users'));
    }
}
