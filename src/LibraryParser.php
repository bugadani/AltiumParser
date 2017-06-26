<?php

namespace AltiumParser;

abstract class LibraryParser
{
    private $ole;

    public function __construct($filename)
    {
        $this->ole = new \OLEReader\OLEReader($filename);
    }

    protected function getOleFile()
    {
        return $this->ole;
    }
}