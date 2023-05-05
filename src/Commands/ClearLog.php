<?php

namespace Devdot\LogArtisan\Commands;

use Devdot\LogArtisan\Models\DriverMultiple;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClearLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:clear
        {channel? : Channel that should be cleared}
        {--all : Clear all channels that are configured}
        ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear log files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {   
        // make sure the all flag is set when no channel is provided
        if(empty($this->argument('channel')) && $this->option('all') === false) {
            $this->error('No channel name provided. If you wish to clear all logs, set the --all option explicitly.');
            return Command::INVALID;
        }

        // now create the channel driver objects safely
        $channels = [];
        if($this->argument('channel')) {
            $channel = $this->argument('channel');
            $channels[] = (string) (is_array($channel) ? $channel[0] : $channel);
            // check if this channel is configured
            if(empty(config('logging.channels.'.$channels[0]))) {
                $this->error('Channel is not configured!');
                return Command::INVALID;
            }
        }
        if($this->option('all')) {
            $channels = [
                config('logging.default'),
                config('logging.deprecations.channel'),
                'emergency',
            ];
        }

        // make sure we have no empty channels
        $channels = array_filter($channels, fn($channel) => $channel !== null);

        // create a multi driver for our channel selection
        $multidriver = new DriverMultiple('', $channels);

        // and load the files through the driver
        $files = $multidriver->getFilenames();

        $this->line('Found '.count($files).' log files');

        // loop through the files and clear them
        foreach($files as $file) {
            $this->line($file);
            // clear the file
            if(unlink($file) == false) {
                $this->error('Failed unlinking file!');
                continue;
            }
            // manually write into log
            Log::build([
                'driver' => 'single',
                'path' => $file,
            ])->info('Log cleared through Artisan console!');
        }

        return Command::SUCCESS;
    }
}
