<?php

namespace MCordingley\LaraLock;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Hashing\Hasher;

final class Facade
{
    private $encrypter;
    private $hasher;

    public function __construct(AeadEncrypter $encrypter, Hasher $hasher)
    {
        $this->encrypter = $encrypter;
        $this->hasher = $hasher;
    }

    public function check(string $value, Authenticatable $user, array $options = []): bool
    {
        try {
            return $this->hasher->check($value, $this->decrypt($user->getAuthPassword(), $user->getAuthIdentifier(), false), $options);
        } catch (DecryptException $exception) {
            return false;
        }
    }

    /**
     * @param string $value
     * @param Authenticatable $user
     * @param array $options
     * @return string
     * @throws Exception
     */
    public function make(string $value, Authenticatable $user, array $options = []): string
    {
        $identifier = $user->getAuthIdentifier();

        if ($identifier === null) {
            throw new Exception('No identifier set on provided user.');
        }

        return $this->encrypt($this->hasher->make($value, $options), $identifier, false);
    }

    public function needsRehash(string $value, Authenticatable $user, array $options = []): bool
    {
        try {
            return $this->hasher->needsRehash($this->decrypt($value, $user->getAuthIdentifier(), false), $options);
        } catch (DecryptException $exception) {
            return true;
        }
    }

    public function decrypt(string $value, string $additionalData, bool $unserialize = true)
    {
        return $this->encrypter->decrypt($value, $additionalData, $unserialize);
    }

    public function encrypt($value, string $additionalData, bool $serialize = true): string
    {
        return $this->encrypter->encrypt($value, $additionalData, $serialize);
    }
}
