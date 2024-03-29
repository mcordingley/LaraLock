<?php

namespace MCordingley\LaraLock;

use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use InvalidArgumentException;
use SodiumException;

final class AeadEncrypter
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

    /**
     * @return string
     * @throws Exception
     */
    public static function generateKey(): string
    {
        return random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES);
    }

    public function decrypt(string $value, string $additionalData, bool $unserialize = true)
    {
        $rawCipherText = base64_decode($value);

        try {
            $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(
                substr($rawCipherText, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
                $additionalData,
                substr($rawCipherText, 0, SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
                $this->key
            );
        } catch (SodiumException $exception) {
            $plaintext = false;
        }

        if ($plaintext === false) {
            throw new DecryptException('Unable to decrypt password.');
        }

        return $unserialize ? unserialize($plaintext) : $plaintext;
    }

    public function encrypt($value, string $additionalData, bool $serialize = true): string
    {
        try {
            $nonce = random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES);
        } catch (Exception $exception) {
            throw new EncryptException($exception->getMessage(), 0, $exception);
        }

        return base64_encode(
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
