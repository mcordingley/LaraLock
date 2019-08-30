<?php

namespace MCordingley\LaraLockTests;

use Illuminate\Contracts\Encryption\DecryptException;
use InvalidArgumentException;
use MCordingley\LaraLock\AeadEncrypter;
use PHPUnit\Framework\TestCase;

final class AeadEncrypterTest extends TestCase
{
    private $encrypter;

    public function setUp(): void
    {
        $this->encrypter = new AeadEncrypter(AeadEncrypter::generateKey());
    }

    public function testEncryption()
    {
        $ciphertext = $this->encrypter->encrypt('Vigenere', 'ad');

        static::assertEquals('Vigenere', $this->encrypter->decrypt($ciphertext, 'ad'));
    }

    public function testBadAdditionalData()
    {
        $ciphertext = $this->encrypter->encrypt('Vigenere', 'ad');

        static::expectException(DecryptException::class);
        $this->encrypter->decrypt($ciphertext, 'fail');
    }

    public function testBadKey()
    {
        static::expectException(InvalidArgumentException::class);
        new AeadEncrypter('fail');
    }
}
