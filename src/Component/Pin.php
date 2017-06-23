<?php

namespace AltiumParser\Component;

class Pin
{
    /**
     * @var \AltiumParser\PropertyRecords\Pin
     */
    private $record;

    public function __construct(\AltiumParser\PropertyRecords\Pin $record)
    {
        $this->record = $record;
    }
}