<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ShowLogTest extends TestCase
{
    public function testRunCommand(): void
    {
        $this->artisan('log:show')
            ->expectsOutputToContain('Showing 10 entries from log channel stack, null')
            ->expectsOutputToContain('No logfiles exist in filesystem!')
            ->assertFailed()
            ->run();
    }

    public function testLogging(): void
    {
        Log::write('error', 'test log entry');
        $this->artisan('log:show')
            ->expectsOutputToContain('testing.ERROR @single:')
            ->expectsOutputToContain('test log entry')
            ->expectsOutputToContain('Found 1 log entries')
            ->assertOk()
            ->run();
    }
}
