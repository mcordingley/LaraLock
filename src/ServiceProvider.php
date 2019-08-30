<?php

namespace MCordingley\Larabits\Encryption;

use Carbon\Laravel\ServiceProvider as BaseProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use MCordingley\LaraLock\Auth\LockedUserProvider;
use MCordingley\LaraLock\AeadEncrypter;
use MCordingley\LaraLock\Facade;
use MCordingley\LaraLock\GenerateKey;

final class ServiceProvider extends BaseProvider
{
    public function register()
    {
        $this->app->singleton('laralock', function (Application $app) {
            return new Facade($app->make(AeadEncrypter::class), $app->make('hash'));
        });

        $this->app->singleton(AeadEncrypter::class, function (Application $app) {
            $key = $app->make('config')->get('laralock.key');

            if (Str::startsWith($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            return new AeadEncrypter($key);
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateKey::class,
            ]);

            $this->publishes([
                __DIR__ . '/config/laralock.php' => config_path('laralock.php'),
            ]);
        }

        Auth::provider('laralock', function (Application $app, array $config) {
            return new LockedUserProvider(
                $this->app['hash'], $config['model'],
                $app->make(AeadEncrypter::class),
                $config['fallthrough'] ?? false
            );
        });
    }
}
