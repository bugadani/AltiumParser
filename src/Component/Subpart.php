<?php

namespace AltiumParser\Component;

class Subpart
{
    private $pins;
    private $drawingPrimitives;

    public function addPin(Pin $pin)
    {
        $this->pins[] = $pin;
    }

    public function addDrawingPrimitive($primitive)
    {
        $this->drawingPrimitives[] = $primitive;
    }
}