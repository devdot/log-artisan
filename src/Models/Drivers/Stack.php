<?php

namespace Devdot\LogArtisan\Models\Drivers;

use Devdot\LogArtisan\Models\DriverMultiple;

class Stack extends DriverMultiple {
    protected function createDrivers(array $channels) {
        if(empty($channels)) {
            $channels = config('logging.channels.'.$this->channel.'.channels') ?? [];
        }
        parent::createDrivers($channels);
    }
}
