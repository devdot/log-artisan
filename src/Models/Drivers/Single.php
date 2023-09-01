<?php

namespace Devdot\LogArtisan\Models\Drivers;

use Devdot\LogArtisan\Models\Driver;

class Single extends Driver
{
    protected function generateFilenames(): void
    {
        // get the file from the config
        $filename = config('logging.channels.' . $this->channel . '.path');
        // check if the file exists
        if (file_exists($filename)) {
            $this->filenames[] = $filename;
        }
    }
}
