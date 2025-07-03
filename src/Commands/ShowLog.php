<?php

namespace Devdot\LogArtisan\Commands;

use Devdot\LogArtisan\Helpers\CommandHelper;
use Devdot\LogArtisan\Models\DriverMultiple;
use Devdot\LogArtisan\Models\LogRecord;
use Illuminate\Console\Command;

class ShowLog extends Command
{
    protected const DEFAULT_COUNT = 10;
    protected const LEVELS = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    protected const READ_BUFFER_SIZE = 8192;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:show
        {--c|count= : Show this amount of entries, default is ' . self::DEFAULT_COUNT . '}
        {--l|level= : Show only entries with this log level}
        {--channel= : Use this specified logging channel}
        {--short : Only show short snippets}
        {--s|singleline : Show single-lined layout}
        {--stacktrace : Show the full stacktrace}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the last log entries';

    protected int $terminalWidth;

    /**
     * @var array{count?: int, level?: string, search?: string}
     */
    protected array $filter = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // get the terminal width
        $this->terminalWidth = (new \Symfony\Component\Console\Terminal())->getWidth();

        // validate input data
        // count
        $this->filter['count'] = empty($this->option('count')) ? self::DEFAULT_COUNT : (int) $this->option('count');

        // level
        if (!empty($this->option('level'))) {
            $level = $this->option('level');
            $this->filter['level'] = (string) (is_array($level) ? $level[0] : $level);
            if (!in_array($this->filter['level'], self::LEVELS)) {
                // this is an invalid log level
                $this->error('Log Level is invalid, try: ' . implode(', ', self::LEVELS));
                return Command::INVALID;
            }
        }

        // channel filtering
        $channels = [];
        if (!empty($this->option('channel'))) {
            $channel = $this->option('channel');
            $channels[] = (string) (is_array($channel) ? $channel[0] : $channel);
            // check if this channel is configured
            if (empty(config('logging.channels.' . $channels[0]))) {
                $this->error('Channel is not configured!');
                return Command::INVALID;
            }
        } else {
            $channels = [
                config('logging.default'),
                config('logging.deprecations.channel'),
            ];
            $channels = array_filter($channels, fn($channel) => $channel !== null);
        }

        // create a multi driver for the default channel
        $multidriver = new DriverMultiple('', $channels);

        // info line at top
        $this->line('Showing <fg=gray>' . $this->filter['count'] . '</> entries from log channel <fg=gray>' . implode(', ', $channels) . '</>' . (isset($this->filter['level']) ? ' at level <fg=gray>' . $this->filter['level'] . '</fg=gray>' : ''));

        // check if we have files at all
        if (count($multidriver->getFilenames()) === 0) {
            $this->warn('Found no configured logfiles besides emergency log!');
        }

        // check if emergency log is already in there
        if (empty($this->option('channel')) && !in_array(config('logging.channels.emergency.path'), $multidriver->getFilenames())) {
            // create new multidriver with emergency log added into the mix
            $channels[] = 'emergency';
            $multidriver = new DriverMultiple('', $channels);
            $this->line('Including emergency channel.');
        }

        // filter these files for those that exist
        if (count($multidriver->getFilenames()) === 0) {
            $this->error('No logfiles exist in filesystem!');
            return Command::FAILURE;
        }

        // get the records, this will accumulate and sort recursively
        $records = $multidriver->getRecords($this->filter);

        if (count($records) === 0) {
            $this->warn('No log entries found!');
            return Command::SUCCESS;
        }

        foreach ($records as $log) {
            if ($this->option('singleline')) {
                $this->printRecordSingleline($log);
            } else {
                $this->printSeparator();
                $this->printRecord($log);
            }
        }

        $this->printSeparator();

        // send info
        $this->newLine();
        $this->line('Found ' . count($records) . ' log entries from ' . $records[0]['datetime']->format('Y-m-d H:i:s') . ' until ' . $records[count($records) - 1]['datetime']->format('Y-m-d H:i:s'));
        $this->newLine();


        return Command::SUCCESS;
    }

    protected function printSeparator(): void
    {
        $this->newLine();
        $this->line('<bg=gray>' . str_pad('', $this->terminalWidth, ' ') . '</>');
        $this->newLine();
    }

    protected function printRecord(LogRecord $record): void
    {
        $this->line(CommandHelper::styleLogRecordHeader($record));
        $this->line($record['message']);
        // stop output here if it's short output
        if ($this->option('short')) {
            return;
        }
        // parse the context object
        // handle the case where context is a string
        $array = is_array($record['context'])
            ? $record['context']
            : (is_object($record['context']) ? get_object_vars($record['context']) : ['context' => $record['context']]);
        foreach ($array as $attribute => $value) {
            // handle exception after the loop
            if ($attribute == 'exception') {
                continue;
            }
            $this->line('<fg=gray>' . $attribute . ':</> ' . var_export($value, true));
        }
        if (isset($array['exception']) && !$this->option('short')) {
            $errorMessage = $array['exception'];
            $stacktrace = '';
            if (strpos($array['exception'], PHP_EOL . '[stacktrace]' . PHP_EOL) !== false) {
                // split at the stacktrace
                $ex = explode(PHP_EOL . '[stacktrace]' . PHP_EOL, $array['exception'], 2);
                $errorMessage = $ex[0];
                $stacktrace = $ex[1];
            }
            $this->line('<fg=gray>exception</>: ' . $errorMessage);
            $this->newLine();
            $this->line('<fg=gray>[stacktrace]</>');
            // split up the stacktrace
            $lines = explode(PHP_EOL, $stacktrace);
            // if we don't have the stacktrace option set, skip most of the stacktrace
            if (!$this->option('stacktrace') && count($lines) > 2) {
                $this->line('<fg=gray>' . $lines[0] . '</>');
                $this->line('<fg=gray>' . $lines[1] . '</>');
                $this->line('<fg=gray>[...]</>');
            } else {
                foreach ($lines as $line) {
                    $this->line('<fg=gray>' . $line . '</>');
                }
            }
        }
    }

    protected function printRecordSingleline(LogRecord $record): void
    {
        // create the heading text
        $str = CommandHelper::styleLogRecordHeader($record);

        // now add the message
        $str .= ' ' . $record['message'];

        // remove line wraps
        $str = str_replace(PHP_EOL, ' ', $str);

        // and create a string without the formatting
        $plain = preg_replace('/<[^\/]*?>(.*?)<\/>/', '$1', $str);

        // and shorten the entire string to fit one line
        if (strlen($plain ?? '') > $this->terminalWidth) {
            // split the text open according to preg match
            $m = [];
            preg_match_all('/(<(?<tag>[^\/]*?)>(?<string>.*?)<\/>|.*?)/', $str, $m, PREG_SET_ORDER);

            // now go through this and make sure we stay short of the line length
            $remaining = $this->terminalWidth;
            $str = '';
            foreach ($m as $match) {
                $sub = '';
                // if we found a tag, handle that
                if (isset($match['tag'])) {
                    // create substring that is not longer than the remaining length
                    $sub = substr($match['string'] ?? '', 0, $remaining);
                    // rebuild the tag
                    $str .= '<' . $match['tag'] . '>' . $sub . '</>';
                } else {
                    // else simply take whatever string was found, but at most the remaining characters in length
                    $sub = substr($match[0], 0, $remaining);
                    $str .= $sub;
                }
                // update remaining depending on the substring we just added
                $remaining -= strlen($sub);

                if ($remaining <= 0) {
                    // should never go below 0 but we include it to be sure
                    break;
                }
            }
        }

        // print the string
        $this->line($str);
    }
}
