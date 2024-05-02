<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SearchLogTest extends TestCase
{
    public function testRunCommand(): void
    {
        $this->artisan('log:search test')
            ->expectsOutputToContain('Running search via log:show for test')
            ->expectsOutputToContain('Showing 10 entries from log channel stack, null')
            ->expectsOutputToContain('No logfiles exist in filesystem!')
            ->assertFailed()
            ->run();
    }

    public function testSearch(): void
    {
        Log::write('error', 'test log entry');
        Log::write('error', 'asdf');
        Log::write('error', 'asdf12');
        Log::write('error', 'not findable');
        $this->artisan('log:show')
            ->expectsOutputToContain('Found 4 log entries')
            ->assertOk()
            ->run();

        $this->artisan('log:search test')
            ->expectsOutputToContain('Found 1 log entries')
            ->expectsOutputToContain('testing.ERROR @single:')
            ->expectsOutputToContain('test log entry')
            ->assertOk()
            ->run();

        $this->artisan('log:search asdf')
            ->expectsOutputToContain('Found 2 log entries')
            ->assertOk()
            ->run();
    }
}
