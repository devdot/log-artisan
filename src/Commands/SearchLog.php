<?php

namespace Devdot\LogArtisan\Commands;

use Devdot\LogArtisan\Models\LogRecord;
use Symfony\Component\Console\Input\InputArgument;

class SearchLog extends ShowLog {
    protected $description = 'Search through all log entries';

    public function __construct() {
        // basically, we use the parent class and add an arugment
        parent::__construct();
        $this->addArgument('search', InputArgument::REQUIRED, 'Search term to be searched for in the logs.');
        $this->setName('log:search');
    }

    public function handle() {
        // basically, we add the search term to the filter and then we let the parent function handle it normally
        $this->filter['search'] = $this->argument('search');
        $this->line('Running search via log:show for <bg=yellow>'.$this->filter['search'].'</>');
        parent::handle();
    }

    protected function addHighlightingToRecord(LogRecord $record): LogRecord {
        $message = str_replace($this->filter['search'], '<bg=yellow>'.$this->filter['search'].'</>', $record['message']);
        $driver = $record->getDriver();
        $record = new LogRecord(
            $record['datetime'],
            $record['channel'],
            $record['level'],
            $message,
            $record['context'],
            $record['extra'],
        );
        $record->setDriver($driver);
        return $record;
    }

    protected function printRecord(LogRecord $record): void {
        // simply add highlighting and then let the parent handle it
        $record = $this->addHighlightingToRecord($record);
        parent::printRecord($record);
    }

    protected function printRecordSingleline(LogRecord $record): void {
        // same as print Record
        // simply add highlighting and then let the parent handle it
        $record = $this->addHighlightingToRecord($record);
        parent::printRecordSingleline($record);
    }
}