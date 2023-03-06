<?php

namespace Devdot\LogArtisan\Helpers;

use Illuminate\Console\Command;

// this helper needs to extend Command in order to access protected properties
class CommandHelper extends Command { 
    /**
     * Print a section component
     */
    public static function displaySection(Command $cmd, string $section, array $data): void {
        $cmd->newLine();
        $cmd->components->twoColumnDetail('  <fg=green;options=bold>'.$section.'</>');
        foreach($data as $key => $value) {
            $cmd->components->twoColumnDetail($key, $value);
        }
    }

    public static function styleDebugLevel(string $level): string {
        switch(strtolower($level)) {
            // sorted in descending order of severity
            case 'emergency':
                $color = 'magenta';
                break;
            case 'alert':
            case 'critical':
                $color = 'bright-red';
                break;
            case 'error':
                $color = 'red';
                break;
            case 'warning':
            case 'notice':
                $color = 'yellow';
                break;
            case 'info':
                $color = 'blue';
                break;
            case 'debug':
                $color = 'gray';
                break;
            default:
                $color = 'white';
                break;
        }
        $value = '<fg='.$color.'>'.strtoupper($level).'</>';
        return $value;
    }
}
