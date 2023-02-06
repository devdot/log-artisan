<?php

namespace Devdot\LogArtisan\Commands;

use Devdot\LogArtisan\Models\Log;
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
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the last log entries';

    protected int $terminalWidth;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {   
        // validate input data
        // count
        $count = empty($this->option('count')) ? self::DEFAULT_COUNT : (int) $this->option('count');
        
        // level
        $level = null;
        if(!empty($this->option('level'))) {
            $level = $this->option('level');
            echo $level;
            if(!in_array($level, self::LEVELS)) {
                // this is an invalid log level
                $this->error('Log Level is invalid, try: '.implode(', ', self::LEVELS));
                return Command::INVALID;
            }
        }

        // channel
        $channel = config('logging.default');
        if(!empty($this->option('channel'))) {
            $channel = $this->option('channel');
            // check if this channel is configured
            if(empty(config('logging.channels.'.$channel))) {
                $this->error('Channel is not configured!');
                return Command::INVALID;
            }
        }

        // get the terminal width
        $this->terminalWidth = (new \Symfony\Component\Console\Terminal)->getWidth();

        // info line at top
        $this->line('Showing <fg=gray>'.$count.'</> entries from log channel <fg=gray>'.$channel.'</>'.($level ? ' at level <fg=gray>'.$level.'</fg=gray>' : ''));

        // get logs for the selected channel
        $files = $this->getFilesFromChannel($channel);

        // check if we have files at all
        if(count($files) === 0) {
            $this->warn('Found no configured logfiles besides emergency log!');
        }
        // merge emergency log always
        $files[] = config('logging.channels.emergency.path');
        
        // filter these files for those that exist
        $files = array_filter($files, fn($file) => file_exists($file));
        if(count($files) === 0) {
            $this->error('No logfiles exist in filesystem!');
            return Command::FAILURE;
        }

        // create file objects from the file names
        $files = array_map(fn($filename) => new Log($filename), $files);

        // run through the files and get their logs
        $logs = [];
        foreach($files as $file) {
            $logs = array_merge($logs, $file->getRecords());
        }

        if(count($logs) === 0) {
            $this->warn('No log entries found!');
            return Command::SUCCESS;
        }

        // now sort the logs by date
        usort($logs, fn($a, $b) => ($a['datetime']->format('U') > $b['datetime']->format('U')));

        // send info
        $this->newLine();
        $this->line(count($logs).' log entries from '.$logs[0]['datetime']->format('Y-m-d H:i:s')).' until '.$logs[count($logs)-1]['datetime']->format('Y-m-d H:i:s');
        $this->newLine();

        // get the last $count items
        $logs = array_slice($logs, -$count);

        $shortLen = ($this->terminalWidth*2)-7;;
        foreach($logs as $log) {
            $this->printSeparator();
            $this->line($log['datetime']->format('Y-m-d H:i:s').' <fg=gray>'.$log['channel'].'</>.'.\Devdot\LogArtisan\Commands\Log::styleDebugLevel($log['level']).':');
            
            if($this->option('short')) {
                // cut off after a certain threshold (2 lines max), make sure it never more than 2 lines
                $short = substr($log['message'], 0, $shortLen);
                $ex = explode(PHP_EOL, $short, 3);
                if(strlen($ex[0]) <= $this->terminalWidth && count($ex) > 1)
                    $short = $ex[0].PHP_EOL.$ex[1];
                else
                    $short = $ex[0];

                // make sure we don't add [...] if we didn't cut
                if(strlen($short) < strlen($log['message']))
                    $short .= '  [...]';
                $this->line($short);
            }
            else {
                $this->line($log['message']);
            }
        }

        $this->newLine();

        return Command::SUCCESS;
    }

    protected function printSeparator() {
        $this->newLine();
        $this->line('<bg=gray>'.str_pad('', $this->terminalWidth, ' ').'</>');
        $this->newLine();
    }

    protected function getFilesFromChannel($channel) {
        $files = [];
        // load the files from this channel
        $config = config('logging.channels.'.$channel);

        // special case with emergency channel
        if($channel == 'emergency')
            $config['driver'] = 'single';
        
        // first switch depending on the driver
        switch($config['driver']) {
            case 'single':
                // simply grab the file that is defined here
                $files[] = $config['path'];
                break;
            case 'stack':
                // load files recursively
                foreach($config['channels'] as $subchannel) {
                    // simply merge recursively
                    $files = array_merge($files, $this->getFilesFromChannel($subchannel));
                }
                break;
            case 'daily':
                // fetch the daily files ...
                break;
            default:
                break;
        }
        // return all the files
        return $files;
    }

    protected function parseLogLines(array $lines, int $timestamp) {
        // EXAMPLE STRING: [2023-01-26 16:39:52] local.ERROR: Call to undefined function ... {"exception":"[object] 
        
            // start object with timestamp
        $log = ['timestamp' => $timestamp];

        // put it all into one string again
        $str = implode(PHP_EOL, $lines);

        // skip past the timestamp
        $pos = strpos($str, ']') + 2;
        // find column that separates header
        $posColumn = strpos($str, ':', $pos);

        // now get the header string
        $header = substr($str, $pos, $posColumn - $pos);

        // separate environment and level
        $ex = explode('.', $header, 2);
        $log['env'] = $ex[0];
        $log['level'] = strtolower($ex[1]);

        // add the message string
        $log['message'] = substr($str, $posColumn + 2);

        return $log;
    }

}
