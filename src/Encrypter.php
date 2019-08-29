<?php

namespace MCordingley\LaraLock;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use InvalidArgumentException;

final class Encrypter
{
    private $key;

    public function __construct(string $key)
    {
        if (strlen($key) !== SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES) {
            throw new InvalidArgumentException(
                'Key must be ' . SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES . ' bytes.'
            );
        }

        $this->key = $key;
    }

    public function __destruct()
    {
        sodium_memzero($this->key);
    }

    public static function generateKey(): string
    {
        return random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES);
    }

    public function decrypt(string $value, string $additionalData, bool $unserialize = true): string
    {
        $rawCipherText = hex2bin($value);

        $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
            substr($rawCipherText, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
            $additionalData,
            substr($rawCipherText, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
            $this->key
        );

        if ($plaintext === false) {
            throw new DecryptException('Unable to decrypt password.');
        }

        return $unserialize ? unserialize($plaintext) : $plaintext;
    }

    public function encrypt($value, string $additionalData, bool $serialize = true): string
    {
        try {
            $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
        } catch (\Exception $exception) {
            throw new EncryptException('Unable to generate nonce, not enough entropy.', 0, $exception);
        }

        return bin2hex(
            $nonce .
            sodium_crypto_aead_chacha20poly1305_ietf_encrypt(
                $serialize ? serialize($value) : $value,
                $additionalData,
                $nonce,
                $this->key
            )
        );
    }
}
