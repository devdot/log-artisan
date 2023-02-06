<?php

namespace Devdot\LogArtisan\Models;

use Devdot\LogArtisan\Models\Drivers\Daily;
use Devdot\LogArtisan\Models\Drivers\Single;
use Devdot\LogArtisan\Models\Drivers\Stack;

class DriverMultiple extends Driver {
    protected array $drivers;

    public function __construct(string $channel, array $channels = []) {
        $this->channel = $channel;
        $this->createDrivers($channels);
        parent::__construct($channel);
    }

    protected function createDrivers(array $channels) {
        foreach($channels as $channel) {
            // create a new driver for each subchannel
            $driver = config('logging.channels.'.$channel.'.driver');
            switch($driver) {
                case 'single':
                    $this->drivers[] = new Single($channel);
                    break;
                case 'daily':
                    $this->drivers[] = new Daily($channel);
                    break;
                case 'stack':
                    $this->drivers[] = new Stack($channel);
                    break;
                case null:
                    // special case for emergency channel
                    if($channel == 'emergency') {
                        $this->drivers[] = new Single($channel);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function getFilenames(): array {
        $this->filenames = [];
        foreach($this->drivers as $driver) {
            $this->filenames = array_merge($this->filenames, $driver->getFilenames());
        }
        return $this->filenames;
    }

    public function getLogs(): array {
        $this->logs = [];
        foreach($this->drivers as $driver) {
            $this->logs = array_merge($this->logs, $driver->getLogs());
        }
        return $this->logs;
    }

    protected function accumulateRecords(array $filter = []) {
        $this->records = [];
        foreach($this->drivers as $driver) {
            $this->records = array_merge($this->records, $driver->getRecords($filter));
        }
        // make sure to sort after merging
        $this->sortRecords();
        return $this->records;
    }
}
