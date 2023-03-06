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
        {--c|count= : Show this amount of entries, default is '.self::DEFAULT_COUNT.'}
        {--l|level= : Show only entries with this log level}
        {--channel= : Use this specified logging channel}
        {--s|short : Only show short snippets}
        {--singleline : Show single-lined layout}
        {--stacktrace : Show the full stacktrace}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the last log entries';

    protected int $terminalWidth;

    protected array $filter = [
        'count' => null,
        'level' => null,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {   
        // get the terminal width
        $this->terminalWidth = (new \Symfony\Component\Console\Terminal)->getWidth();

        // validate input data
        // count
        $this->filter['count'] = empty($this->option('count')) ? self::DEFAULT_COUNT : (int) $this->option('count');
        
        // level
        if(!empty($this->option('level'))) {
            $this->filter['level'] = $this->option('level');
            if(!in_array($this->filter['level'], self::LEVELS)) {
                // this is an invalid log level
                $this->error('Log Level is invalid, try: '.implode(', ', self::LEVELS));
                return Command::INVALID;
            }
        }

        // channel filtering
        $channels = [];
        if(!empty($this->option('channel'))) {
            $channels[] = $this->option('channel');
            // check if this channel is configured
            if(empty(config('logging.channels.'.$channels[0]))) {
                $this->error('Channel is not configured!');
                return Command::INVALID;
            }
        }
        else {
            $channels = [
                config('logging.default'),
                config('logging.deprecations.channel'),
            ];
            $channels = array_filter($channels, fn($channel) => $channel !== null);
        }

        // create a multi driver for the default channel
        $multidriver = new DriverMultiple('', $channels);
        
        // info line at top
        $this->line('Showing <fg=gray>'.$this->filter['count'].'</> entries from log channel <fg=gray>'.implode(', ', $channels).'</>'.($this->filter['level'] ? ' at level <fg=gray>'.$this->filter['level'].'</fg=gray>' : ''));

        // check if we have files at all
        if(count($multidriver->getFilenames()) === 0) {
            $this->warn('Found no configured logfiles besides emergency log!');
        }

        // check if emergency log is already in there
        if(empty($this->option('channel')) && !in_array(config('logging.channels.emergency.path'), $multidriver->getFilenames())) {
            // create new multidriver with emergency log added into the mix
            $channels[] = 'emergency';
            $multidriver = new DriverMultiple('', $channels);
            $this->line('Including emergency channel.');
        }
        
        // filter these files for those that exist
        if(count($multidriver->getFilenames()) === 0) {
            $this->error('No logfiles exist in filesystem!');
            return Command::FAILURE;
        }

        // get the records, this will accumulate and sort recursively
        $records = $multidriver->getRecords($this->filter);

        if(count($records) === 0) {
            $this->warn('No log entries found!');
            return Command::SUCCESS;
        }

        foreach($records as $log) {
            if($this->option('singleline'))
                $this->printRecordSingleline($log);
            else {
                $this->printSeparator();
                $this->printRecord($log);
            }
        }

        $this->printSeparator();

        // send info
        $this->newLine();
        $this->line('Found '.count($records).' log entries from '.$records[0]['datetime']->format('Y-m-d H:i:s').' until '.$records[count($records)-1]['datetime']->format('Y-m-d H:i:s'));
        $this->newLine();


        return Command::SUCCESS;
    }

    protected function printSeparator() {
        $this->newLine();
        $this->line('<bg=gray>'.str_pad('', $this->terminalWidth, ' ').'</>');
        $this->newLine();
    }

    protected function printRecord(LogRecord $record): void {
        $this->line(CommandHelper::styleLogRecordHeader($record));
        $this->line($record['message']);
        // stop output here if it's short output
        if($this->option('short')) {
            return;
        }
        // parse the context object
        $array = is_array($record['context']) ? $record['context'] : get_object_vars($record['context']);
        foreach($array as $attribute => $value) {
            // handle exception after the loop
            if($attribute == 'exception')
                continue;
            $this->line('<fg=gray>'.$attribute.':</> '.var_export($value, true));
        }
        if(isset($array['exception']) && !$this->option('short')) {
            $errorMessage = $array['exception'];
            $stacktrace = '';
            if(strpos($array['exception'], PHP_EOL.'[stacktrace]'.PHP_EOL) !== false) {
                // split at the stacktrace
                $ex = explode(PHP_EOL.'[stacktrace]'.PHP_EOL, $array['exception'], 2);
                $errorMessage = $ex[0];
                $stacktrace = $ex[1];
            }
            $this->line('<fg=gray>exception</>: '.$errorMessage);
            $this->newLine();
            $this->line('<fg=gray>[stacktrace]</>');
            // split up the stacktrace
            $lines = explode(PHP_EOL, $stacktrace);
            // if we don't have the stacktrace option set, skip most of the stacktrace
            if(!$this->option('stacktrace') && count($lines) > 2) {
                $this->line('<fg=gray>'.$lines[0].'</>');
                $this->line('<fg=gray>'.$lines[1].'</>');
                $this->line('<fg=gray>[...]</>');
            }
            else {
                foreach($lines as $line) {
                    $this->line('<fg=gray>'.$line.'</>');
                }
            }

        }   
    }

    protected function printRecordSingleline(LogRecord $record): void {
        // create the heading text
        $str = CommandHelper::styleLogRecordHeader($record);
        
        // now add the message
        $str .= ' '.$record['message'];

        // remove line wraps
        $str = str_replace(PHP_EOL, ' ', $str);

        // and create a string without the formatting
        $plain = preg_replace('/<[^\/]*?>(.*?)<\/>/', '$1', $str);

        // and shorten the entire string to fit one line
        if(strlen($plain) > $this->terminalWidth) {
            // calculate the allowed length by adding the formatting length
            $formattingLength = strlen($str) - strlen($plain);

            // make it shorter
            $str = substr($str, 0, $this->terminalWidth + $formattingLength);

            // and make sure we are closing every formatting properly
            if(substr($str, -2) === '</')
                $str = substr($str, 0, -3).'</>';

            // count to make sure we close every opened formatting tag
            $countOpen = count(preg_split('/<[^\/]*?>/', $str));
            $countClose = count(preg_split('/<\/>/', $str));

            // check if we are odd between open and close
            if($countOpen > $countClose) {
                // make sure we didn't cut a closing tag short
                if(substr($str, -1) == '<')
                    $str = substr($str, 0, -3);

                // append for each missing one the close tag
                for($i = $countClose; $i < $countOpen; $i++)
                    $str .= '</>';
            }
        }

        // print the string
        $this->line($str);
    }
}
