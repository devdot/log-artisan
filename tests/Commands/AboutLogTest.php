<?php

namespace Tests\Commands;

use Tests\TestCase;

class AboutLogTest extends TestCase
{
    public function testRunCommand(): void
    {
        $this->artisan('log:about')
            ->expectsOutputToContain('Channel: stack')
            ->expectsOutputToContain('Channel: single')
            ->assertOk()
            ->run();
    }

    public function testWithoutEmergencyChannel(): void
    {
        // remove emergency from settings
        $config = config('logging.channels');
        $configWithoutEmergency = array_filter($config, fn(string $key): bool => $key !== 'emergency', ARRAY_FILTER_USE_KEY);
        config(['logging.channels' => $configWithoutEmergency]);

        $this->artisan('log:about')
            ->doesntExpectOutputToContain('Channel: emergency (internal)')
            ->assertOk()
            ->run();

        // add again
        config(['logging.channels' => $config]);

        $this->artisan('log:about')
            ->expectsOutputToContain('Channel: emergency (internal)')
            ->assertOk()
            ->run();
    }
}
