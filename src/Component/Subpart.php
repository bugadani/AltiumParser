<?php

namespace AltiumParser\Component;

/**
 * Part of a component, contains pins and various drawing primitives (rectangles, lines, etc.)
 *
 * @package AltiumParser\Component
 */
class Subpart
{
    /**
     * @var Pin[]
     */
    private $pins = [];

    /**
     * @var DrawingPrimitive[]
     */
    private $drawingPrimitives = [];

    public function addPin(Pin $pin)
    {
        $this->pins[] = $pin;
    }

    public function addDrawingPrimitive(DrawingPrimitive $primitive)
    {
        $this->drawingPrimitives[] = $primitive;
    }

    /**
     * @return Pin[]
     */
    public function getPins()
    {
        return $this->pins;
    }

    /**
     * @return DrawingPrimitive[]
     */
    public function getDrawingPrimitives()
    {
        return $this->drawingPrimitives;
    }
}