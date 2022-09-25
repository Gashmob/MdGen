<?php

namespace Gashmob\Mdgen\Test\fulls\NestedValue;

class A
{
    public $a;

    /**
     * @param $a
     */
    public function __construct($a)
    {
        $this->a = $a;
    }
}