<?php

namespace MCordingley\LaraLock;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool check(string $value, Authenticatable $user, array $options = [])
 * @method static string make(string $value, Authenticatable $user, array $options = [])
 * @method static bool needsRehash(string $value, Authenticatable $user, array $options = [])
 * @method static string decrypt(string $value, string $additionalData, bool $unserialize = true)
 * @method static string encrypt($value, string $additionalData, bool $serialize = true)
 *
 * @see \MCordingley\LaraLock\Facade
 */
final class LaraLock extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'laralock';
    }
}
