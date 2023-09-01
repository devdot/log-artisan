<?php

namespace Devdot\LogArtisan\Models;

class LogRecord extends \Devdot\Monolog\LogRecord
{
    private Driver $driver;

    public function getDriver(): Driver
    {
        return $this->driver;
    }

    public function setDriver(Driver $driver): void
    {
        $this->driver = $driver;
    }
}
