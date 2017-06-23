<?php

namespace AltiumParser\PropertyRecords;

class Component extends BaseRecord
{
    public function getLibReference()
    {
        return $this->getProperty('LIBREFERENCE');
    }

    public function getPartIdLocked()
    {
        return $this->getBoolean('PARTIDLOCKED');
    }

    public function getDesignatorLocked()
    {
        return $this->getBoolean('DESIGNATORLOCKED');
    }

    public function getPinsMoveable()
    {
        return $this->getBoolean('PINSMOVEABLE');
    }

    public function getSubpartCount()
    {
        return $this->getInteger('PARTCOUNT') - 1;
    }
}