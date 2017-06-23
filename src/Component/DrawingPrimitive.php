<?php

namespace AltiumParser\Component;

use AltiumParser\PropertyRecords\BaseRecord;

class DrawingPrimitive
{
    /**
     * @var BaseRecord
     */
    private $record;

    /**
     * DrawingPrimitive constructor.
     * @param BaseRecord $r
     */
    public function __construct(BaseRecord $r)
    {
        $this->record = $r;
    }
}