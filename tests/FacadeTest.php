<?php

namespace MCordingley\LaraLockTests;

use Exception;
use Illuminate\Auth\GenericUser;
use Illuminate\Hashing\BcryptHasher;
use MCordingley\LaraLock\AeadEncrypter;
use MCordingley\LaraLock\Facade;
use PHPUnit\Framework\TestCase;

final class FacadeTest extends TestCase
{
    /** @var Facade */
    private $facade;

    public function setUp(): void
    {
        $this->facade = new Facade(new AeadEncrypter(base64_decode('/dgzsXQlhjIRRMIu9mt/IU3N54a8njxKK1zvAUYzzbI=')), new BcryptHasher(['rounds' => 4]));
    }

    public function testHashing()
    {
        $user = new GenericUser(['id' => 1]);
        $user->password = $this->facade->make('foo', $user);

        static::assertTrue($this->facade->check('foo', $user));
    }

    public function testCheckBadValue()
    {
        static::assertFalse($this->facade->check('fail', new GenericUser(['id' => 1, 'password' => 'fail'])));
    }

    public function testNewUser()
    {
        $user = static::createMock(GenericUser::class);

        // GenericUser will have an undefined index, but a real model would return null.
        $user->expects($this->once())
            ->method('getAuthIdentifier')
            ->willReturn(null);

        static::expectException(Exception::class);
        $this->facade->make('foo', $user);
    }

    public function testNeedsRehash()
    {
        $user = new GenericUser(['id' => 1]);
        $user->password = $this->facade->make('foo', $user);

        static::assertFalse($this->facade->needsRehash($user->password, $user));
        static::assertTrue($this->facade->needsRehash('foo', $user));
    }

    public function testEncryption()
    {
        $ciphertext = $this->facade->encrypt('Vigenere', 'ad');

        static::assertEquals('Vigenere', $this->facade->decrypt($ciphertext, 'ad'));
    }
}
