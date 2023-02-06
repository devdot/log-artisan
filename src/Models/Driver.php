<?php

namespace Devdot\LogArtisan\Models;

class Driver {
    protected string $channel;
    protected array $filenames = [];
    protected array $logs = [];
    protected array $records;

    public function __construct(string $channel) {
        $this->channel = $channel;
        $this->generateFilenames();
        $this->createLogs();
    }

    protected function generateFilenames() {
        $this->filenames = [];
    }

    protected function createLogs() {
        $this->logs = array_map(fn($filename) => new Log($filename), $this->filenames);
    }

    public function getLaravelChannel(): string {
        return $this->channel;
    }

    public function getFilenames(): array {
        return $this->filenames;
    }

    public function getLogs(): array {
        return $this->logs;
    }

    public function getRecords(): array {
        // check if we have generated it before
        if(!isset($this->records) || empty($this->records)) {
            $this->accumulateRecords();
        }
        return $this->records;
    }

    protected function accumulateRecords() {
        $this->records = [];
        foreach($this->logs as $log) {
            // get all the records from this logfile and merge them into the others
            $this->records = array_merge($this->records, $log->getRecords());
        }
        // finally sort the results
        $this->sortRecords();
    }

    protected function sortRecords() {
        usort($this->records, fn($a, $b) => ($a['datetime']->format('U') > $b['datetime']->format('U')));
    }
}
