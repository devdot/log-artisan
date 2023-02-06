<?php

namespace Devdot\LogArtisan\Models;

class LogRecord extends \Devdot\Monolog\LogRecord {
    private Driver $driver;

    public function getDriver() {
        return $this->driver;
    }

    public function setDriver(Driver $driver) {
        $this->driver = $driver;
    }
}
