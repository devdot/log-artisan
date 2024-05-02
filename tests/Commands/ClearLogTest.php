<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ClearLogTest extends TestCase
{
    public function testRunCommand(): void
    {
        $this->artisan('log:clear')
            ->assertFailed()
            ->expectsOutputToContain('No channel name provided.')
            ->run();
    }

    public function testClearOnEmptyFile(): void
    {
        $this->artisan('log:clear single')
            ->expectsOutput('Found 0 log files')
            ->assertOk()
            ->run();
    }

    public function testClearChannel(): void
    {
        Log::error('test');

        $this->artisan('log:clear single')
            ->expectsOutput('Found 1 log files')
            ->expectsOutput(self::TMP_LOGS_FOLDER . '/laravel.log')
            ->assertOk()
            ->run();

        $this->artisan('log:show')
            ->expectsOutput('Log cleared through Artisan console!')
            ->assertOk()
            ->run();
    }
}
