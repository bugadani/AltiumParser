<?php

namespace AltiumParser;

class ProjPcbParser
{
    /**
     * @var bool
     */
    private $fileParsed = false;

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    private function ensureFileParsed()
    {
        if (!$this->fileParsed) {
            $this->fileParsed = true;
            $this->parse();
        }
    }

    private function parse()
    {
        $ini = parse_ini_file($this->filename, true, INI_SCANNER_RAW);
    }
}