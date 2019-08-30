<?php

namespace MCordingley\LaraLock\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use MCordingley\LaraLock\Encrypter;

final class LockedUserProvider extends EloquentUserProvider
{
    private $encrypter;
    private $fallthrough;

    public function __construct(HasherContract $hasher, string $model, Encrypter $encrypter, bool $fallthrough)
    {
        parent::__construct($hasher, $model);

        $this->encrypter = $encrypter;
        $this->fallthrough = $fallthrough;
    }

    public function validateCredentials(UserContract $user, array $credentials)
    {
        $hash = $user->getAuthPassword();

        try {
            $hash = $this->encrypter->decrypt($hash, $user->getAuthIdentifier(), false);
        } catch (DecryptException $exception) {
            if (!$this->fallthrough) {
                return false;
            }
        }

        return $this->hasher->check($credentials['password'], $hash);
    }
}
