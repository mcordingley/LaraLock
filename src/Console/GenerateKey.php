<?php

namespace MCordingley\LaraLock;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

final class GenerateKey extends Command
{
    use ConfirmableTrait;

    protected $signature = 'laralock:key:generate
                    {--show : Display the keys instead of modifying files}
                    {--force : Force the operation to run when in production}';

    protected $description = 'Set LaraLock encryption key.';

    public function handle()
    {
        $key = 'base64:' . base64_encode(Encrypter::generateKey());

        if ($this->option('show')) {
            $this->line('<comment>'.$key.'</comment>');

            return;
        }

        if (!$this->laravel['config']['laralock.key'] || $this->confirmToProceed()) {
            $this->laravel['config']['laralock.key'] = $key;
            $this->writeConfigurationValue('LARALOCK_KEY', $key);

            $this->info('LaraLock key set successfully.');
        }
    }

    final protected function writeConfigurationValue(string $key, string $value): void
    {
        $pattern = "/^$key=.*$/m";
        $line = $key . '=' . $value;

        $contents = file_get_contents($this->laravel->environmentFilePath());
        $updated = preg_match($pattern, $contents) ? preg_replace($pattern, $line, $contents) : $contents . "\n" . $line;

        file_put_contents($this->laravel->environmentFilePath(), $updated);
    }
}
