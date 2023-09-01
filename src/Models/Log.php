<?php

namespace Devdot\LogArtisan\Models;

use Devdot\Monolog\Parser;

class Log
{
    private Parser $parser;
    private string $filename = '';


    public function __construct(string $filename = '')
    {
        // initialize the parser
        $this->parser = new Parser();
        $this->parser->setPattern(Parser::PATTERN_LARAVEL);
        $this->parser->setOptions(Parser::OPTION_JSON_FAIL_SOFT);

        if (!empty($filename)) {
            $this->setFile($filename);
        }
    }

    public function setFile(string $filename): bool
    {
        // set in object
        $this->filename = $filename;
        // now attempt to set in parser
        try {
            $this->parser->setFile($filename);
        } catch (\Devdot\Monolog\Exceptions\FileNotFoundException $e) {
            return false;
        }
        return true;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getRecords(): \Devdot\Monolog\Log
    {
        // simply access the parser
        return $this->parser->get();
    }
}
