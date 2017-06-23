<?php

namespace AltiumParser\Component;

class Parameter
{
    /**
     * @var \AltiumParser\PropertyRecords\Parameter
     */
    private $record;

    public function __construct(\AltiumParser\PropertyRecords\Parameter $record)
    {
        $this->record = $record;
    }

    public function getUniqueId()
    {
        return $this->record->getProperty('UNIQUEID', '');
    }

    public function getName()
    {
        return $this->record->getProperty('NAME');
    }

    public function getText()
    {
        return $this->record->getProperty('TEXT');
    }

    public function __toString()
    {
        return $this->getText();
    }
}