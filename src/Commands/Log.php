<?php

namespace Devdot\LogArtisan\Commands;

use Illuminate\Console\Command;

class Log extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overview of the current log status';

    protected const STR_NULL = '<fg=yellow>-</>';
    protected const STR_SECRET = '<fg=gray>****</>';
    protected const STR_TRUE = '<fg=gray>true</>';
    protected const STR_FALSE = '<fg=gray>false</>';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {   
        // gather all channels as they are used     
        $mainChannels = [
            'Default Channel' => config('logging.default'), 
            'Deprecations Channel' => config('logging.deprecations.channel'),
        ];
       
        // now parse the main channels for their used channels
        $channels = [];
        foreach($mainChannels as $key => $channel) {
            if($channel == null) {
                // overwrite with null string
                $mainChannels[$key] = self::STR_NULL;
                continue;
            }

            $channels[$channel] = [];

            // check if this channel has sub-channels
            if(count(config('logging.channels.'.$channel.'.channels', []))) {
                $subchannels = config('logging.channels.'.$channel.'.channels');
                foreach($subchannels as $sub) {
                    $channels[$sub] = [];
                }

                // update the channel string for this main channel
                $mainChannels[$key] = $channel.': '.implode(', ', $subchannels);
            }
        }

        $config = [
            'Configuration Cache' => $this->laravel->configurationIsCached() ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
            'Global Log Level' => env('LOG_LEVEL', self::STR_NULL),
        ];

        $this->display('Logging Configuration', array_merge($config, $mainChannels));

        // get data from channels
        foreach($channels as $channel => $arr) {
            $config = config('logging.channels.'.$channel);
            $display = [];
            // now sift through the config
            foreach($config as $key => $value) {
                // make arrays into strings
                if(is_array($value)) {
                    $value = implode(', ', $value);
                }

                switch($key) {
                    // filter away what shouldnt be shown
                    case 'username':
                    case 'password':
                    case 'handler_with':
                    case 'url':
                        $value = self::STR_SECRET;
                        break;
                }

                if(is_bool($value)) {
                    $value = $value ? self::STR_TRUE : self::STR_FALSE;
                }

                $display[$key] = $value ?? self::STR_NULL;
            }

            $this->display('Channel: '.$channel, $display);
        }
        
        $this->newLine();

        return Command::SUCCESS;
    }

    protected function display($section, $data) {
        $this->newLine();
        $this->components->twoColumnDetail('  <fg=green;options=bold>'.$section.'</>');
        foreach($data as $key => $value) {
            $this->components->twoColumnDetail($key, $value);
        }
    }
}
