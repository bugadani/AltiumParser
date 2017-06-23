<?php

namespace AltiumParser\Component;

/**
 * Class containing Pin information. Belongs to a subpart
 *
 * @package AltiumParser\Component
 */
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