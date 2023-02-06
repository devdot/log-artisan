<?php

namespace Devdot\LogArtisan\Commands;

use Devdot\LogArtisan\Models\DriverMultiple;
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

        // create a multi driver for the default channel
        $channels = [
            config('logging.default'),
            config('logging.deprecations.channel'),
        ];
        $multidriver = new DriverMultiple('', $channels);
        // check if emergency log is already in there
        if(!in_array(config('logging.channels.emergency.path'), $multidriver->getFilenames())) {
            // create new multidriver with emergency log added into the mix
            $channels[] = 'emergency';
            $multidriver = new DriverMultiple('', $channels);
        }

        // check if we have files at all
        if(count($multidriver->getFilenames()) === 0) {
            $this->warn('Found no configured logfiles besides emergency log!');
        }

        // check if emergency log is already in there
        if(!in_array(config('logging.channels.emergency.path'), $multidriver->getFilenames())) {
            // create new multidriver with emergency log added into the mix
            $channels[] = 'emergency';
            $multidriver = new DriverMultiple('', $channels);
        }
        
        // filter these files for those that exist
        if(count($multidriver->getFilenames()) === 0) {
            $this->error('No logfiles exist in filesystem!');
            return Command::FAILURE;
        }

        // get the records, this will accumulate and sort recursively
        $records = $multidriver->getRecords();

        if(count($records) === 0) {
            $this->warn('No log entries found!');
            return Command::SUCCESS;
        }

        // get the last $count items
        $logs = array_slice($records, -$count);

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

        $this->printSeparator();

        // send info
        $this->newLine();
        $this->line('Processed '.count($records).' log entries from '.$records[0]['datetime']->format('Y-m-d H:i:s').' until '.$records[count($records)-1]['datetime']->format('Y-m-d H:i:s').', showed '.count($logs));
        $this->newLine();


        return Command::SUCCESS;
    }

    protected function printSeparator() {
        $this->newLine();
        $this->line('<bg=gray>'.str_pad('', $this->terminalWidth, ' ').'</>');
        $this->newLine();
    }
}
