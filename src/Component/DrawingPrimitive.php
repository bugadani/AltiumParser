<?php
/**
 * Created by PhpStorm.
 * User: bugad
 * Date: 2017. 06. 23.
 * Time: 15:08
 */

namespace AltiumParser\Component;


use AltiumParser\PropertyRecords\BaseRecord;

class DrawingPrimitive
{
    /**
     * @var BaseRecord
     */
    private $rrecord;

    /**
     * DrawingPrimitive constructor.
     * @param BaseRecord $r
     */
    public function __construct(BaseRecord $r)
    {
        $this->rrecord = $r;
    }
}