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
       
        $config = [
            'Configuration Cache' => $this->laravel->configurationIsCached() ? '<fg=green;options=bold>CACHED</>' : '<fg=yellow;options=bold>NOT CACHED</>',
            'Global Log Level' => env('LOG_LEVEL', self::STR_NULL),
        ];

        $this->display('Logging Configuration', array_merge($config, $mainChannels));

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
