<?php

namespace Devdot\LogArtisan\Models;

use Devdot\Monolog\LogRecord as BaseLogRecord;

readonly class LogRecord extends BaseLogRecord
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
