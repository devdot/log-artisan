<?php

namespace Devdot\LogArtisan\Models;

class Driver {
    protected string $channel;
    /**
     * @var array<int, string>
     */
    protected array $filenames = [];
    /**
     * @var array<int, Log>
     */
    protected array $logs = [];
    /**
     * @var array<int, LogRecord>
     */
    protected array $records;

    public function __construct(string $channel) {
        $this->channel = $channel;
        $this->generateFilenames();
        $this->createLogs();
    }

    protected function generateFilenames(): void {
        $this->filenames = [];
    }

    protected function createLogs(): void {
        $this->logs = array_map(fn($filename) => new Log($filename), $this->filenames);
    }

    public function getLaravelChannel(): string {
        return $this->channel;
    }

    /**
     * @return array<string>
     */
    public function getFilenames(): array {
        return $this->filenames;
    }

    /**
     * @return array<Log>
     */
    public function getLogs(): array {
        return $this->logs;
    }

    /**
     * @param array{count?: int, level?: string, search?: string} $filter Filter array as provided by ShowLog
     * @return array<LogRecord>
     */
    public function getRecords(array $filter = []): array {
        // check if we have generated it before
        if(!isset($this->records) || empty($this->records)) {
            $this->accumulateRecords($filter);
        }
        // now run the filtering
        return $this->getFilteredRecords($filter);
    }

    /**
     * @param array{count?: int, level?: string, search?: string} $filter Filter array as provided by ShowLog
     * @return array<LogRecord>
     */
    protected function getFilteredRecords(array $filter): array {
        $records = $this->records;
        if(isset($filter['level'])) {
            // filter all that have this level
            $level = strtolower($filter['level']);
            $records = array_filter($records, fn($record) => strtolower($record['level']) == $level);
        }
        if(isset($filter['search'])) {
            $records = array_filter($records, fn($record) => (preg_match('/'.$filter['search'].'/i', $record['message']) === 1));
        }
        if(isset($filter['count']) && count($records) > $filter['count']) {
            // only return the last $count
            $records = array_slice($records, -((int)$filter['count']));
        }
        return $records;
    }

    /**
     * @param array<string, int|string>  $filter
     */
    protected function accumulateRecords(array $filter = []): void {
        $this->records = [];
        foreach($this->logs as $log) {
            // get all the records from this logfile and merge them into the others
            // we iterate with double foreach so we can do a typecast
            foreach($log->getRecords() as $record) {
                // cast the record
                $record = new LogRecord(
                    $record['datetime'],
                    $record['channel'],
                    $record['level'],
                    $record['message'],
                    $record['context'],
                    $record['extra'],
                );
                // add ourselves as driver
                $record->setDriver($this);
                // add to the list
                $this->records[] = $record;
            }
        }
        // finally sort the results
        $this->sortRecords();
    }

    protected function sortRecords(): void {
        usort($this->records, fn($a, $b): int => (int) ($a['datetime']->format('U') > $b['datetime']->format('U')));
    }
}
