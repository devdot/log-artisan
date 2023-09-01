<?php

namespace Devdot\LogArtisan\Models\Drivers;

use Devdot\LogArtisan\Models\DriverMultiple;

class Stack extends DriverMultiple
{
    /**
     * @param array<int, string> $channels
     */
    protected function createDrivers(array $channels): void
    {
        if (empty($channels)) {
            $channels = config('logging.channels.' . $this->channel . '.channels') ?? [];
        }
        parent::createDrivers($channels);
    }
}
