<?php

namespace Devdot\LogArtisan\Models\Drivers;

use Devdot\LogArtisan\Models\Driver;

class Daily extends Driver
{
    protected function generateFilenames(): void
    {
        // get the config data
        $filename = config('logging.channels.' . $this->channel . '.path');
        $days = config('logging.channels.' . $this->channel . '.days');
        // create theoretical filenames for daily log rotation
        $this->filenames = [];
        for ($day = 0; $day <= $days; $day++) {
            // build filename
            $time = strtotime('today -' . $day . ' days');
            $dayFilename = substr($filename, 0, -4) . '-' . date('Y-m-d', (int) $time) . '.log';
            // and check if it exists
            if (file_exists($dayFilename)) {
                $this->filenames[] = $dayFilename;
            }
        }
    }
}
