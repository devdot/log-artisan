<?php

namespace Devdot\LogArtisan\Commands;

use Devdot\LogArtisan\Helpers\CommandHelper;
use Illuminate\Console\Command;

class AboutLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:about';

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

        // add the emergency channel
        $channels['emergency (internal)'] = array_merge(
            config('logging.channels.emergency'),
            ['level' => 'emergency'],
        );

        foreach($mainChannels as $key => $channel) {
            if($channel == null) {
                // overwrite with null string
                $mainChannels[$key] = self::STR_NULL;
                continue;
            }

            $channels[$channel] = config('logging.channels.'.$channel);

            // check if this channel has sub-channels
            if(count(config('logging.channels.'.$channel.'.channels', []))) {
                $subchannels = config('logging.channels.'.$channel.'.channels');
                foreach($subchannels as $sub) {
                    $channels[$sub] = config('logging.channels.'.$sub);
                }

                // update the channel string for this main channel
                $mainChannels[$key] = $channel.': '.implode(', ', $subchannels);
            }
        }

        $config = [
            'Configuration Cache' => $this->laravel->configurationIsCached() ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
            'Global Log Level' => env('LOG_LEVEL') ? CommandHelper::styleDebugLevel(env('LOG_LEVEL')) : self::STR_NULL,
        ];

        CommandHelper::displaySection($this, 'Logging Configuration', array_merge($config, $mainChannels));

        // get data from channels
        foreach($channels as $channel => $config) {
            // make sure each channel has driver and level

            // custom sort the config: first driver, level, path, rest alphabetical
            $arr = [
                'driver' => $config['driver'] ?? null,
                'level' => $config['level'] ?? null,
            ];
            if(isset($config['path'])) $arr['path'] = $config['path'];
            ksort($config);
            $config = array_merge($arr, $config);

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
                    case 'path':
                        // now check if this file exists and when it was modified
                        if(file_exists($value)) {
                            $value = '<fg=gray><'.date('Y-m-d H:i:s', (int) filemtime($value)).'></> '.$value;
                        }
                        else {
                            $value = '<fg=gray><file missing></> <fg=red>'.$value.'</>';
                        }
                        break;
                    case 'level':
                        // style the level
                        $value = $value ? CommandHelper::styleDebugLevel($value) : self::STR_NULL;
                        break;
                }

                if(is_bool($value)) {
                    $value = $value ? self::STR_TRUE : self::STR_FALSE;
                }

                $display[$key] = $value ?? self::STR_NULL;
            }

            CommandHelper::displaySection($this, 'Channel: '.$channel, $display);
        }
        
        $this->newLine();

        return Command::SUCCESS;
    }
}
