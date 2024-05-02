<?php

namespace Tests;

use Devdot\LogArtisan\ServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class TestCase extends BaseTestCase
{
    const TMP_LOGS_FOLDER = __DIR__ . '/tmp';

    public function setUp(): void
    {
        parent::setUp();
        $this->clearLogs();
    }

    protected function clearLogs(): void
    {
        if (is_dir(self::TMP_LOGS_FOLDER)) {
            foreach (scandir(self::TMP_LOGS_FOLDER) as $file) {
                $path = self::TMP_LOGS_FOLDER . '/' . $file;
                if (is_file($path)) {
                    unlink($path);
                }
            }
            rmdir(self::TMP_LOGS_FOLDER);
        }

        if (!is_dir(self::TMP_LOGS_FOLDER)) {
            mkdir(self::TMP_LOGS_FOLDER, 0777, true);
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            $config->set('logging.default', 'stack');
            $config->set('logging.channels', [
                'stack' => [
                    'driver' => 'stack',
                    'channels' => ['single'],
                    'ignore_exceptions' => false,
                ],
                'single' => [
                    'driver' => 'single',
                    'path' => self::TMP_LOGS_FOLDER . '/laravel.log',
                    'level' => env('LOG_LEVEL', 'debug'),
                    'replace_placeholders' => true,
                ],
                'emergency' => [
                    'path' => self::TMP_LOGS_FOLDER . '/emergency.log',
                ],
            ]);
        });
    }

    protected function printArtisanOutput(string $command, array $parameters = []): void
    {
        $output = new BufferedOutput();
        Artisan::call($command, $parameters, $output);
        echo PHP_EOL;
        echo '> ' . $command . PHP_EOL;
        echo $output->fetch();
        echo PHP_EOL;
    }
}
