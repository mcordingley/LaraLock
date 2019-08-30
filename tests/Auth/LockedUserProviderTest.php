<?php

namespace MCordingley\LaraLockTests\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Hashing\BcryptHasher;
use MCordingley\LaraLock\AeadEncrypter;
use MCordingley\LaraLock\Auth\LockedUserProvider;
use MCordingley\LaraLock\Facade;
use PHPUnit\Framework\TestCase;

final class LockedUserProviderTest extends TestCase
{
    public function testValidateCredentials()
    {
        $encrypter = new AeadEncrypter(base64_decode('/dgzsXQlhjIRRMIu9mt/IU3N54a8njxKK1zvAUYzzbI='));
        $hasher = new BcryptHasher(['cost' => 4]);
        $facade = new Facade($encrypter, $hasher);

        $provider = new LockedUserProvider(
            $hasher,
            'foo',
            $encrypter,
            false
        );

        $user = new GenericUser(['id' => 1]);
        $user->password = $facade->make('test', $user);

        static::assertTrue($provider->validateCredentials($user, [
            'id' => 1,
            'password' => 'test',
        ]));
    }
    public function testValidateBadCredentials()
    {
        $encrypter = new AeadEncrypter(base64_decode('/dgzsXQlhjIRRMIu9mt/IU3N54a8njxKK1zvAUYzzbI='));
        $hasher = new BcryptHasher(['cost' => 4]);

        $provider = new LockedUserProvider(
            $hasher,
            'foo',
            $encrypter,
            false
        );

        $user = new GenericUser(['id' => 1]);
        $user->password = 'fail';

        static::assertFalse($provider->validateCredentials($user, [
            'id' => 1,
            'password' => 'test',
        ]));
    }
}
