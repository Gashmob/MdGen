<?php

namespace Gashmob\Mdgen;

final class EngineState
{
    const INIT = 0;
    const OLIST = 1;
    const ULIST = 2;
    const CODE = 3;
    const QUOTE = 4;

    public $state;
    public $level;

    public function __construct($state = self::INIT, $level = -1)
    {
        $this->state = $state;
        $this->level = $level;
    }
}