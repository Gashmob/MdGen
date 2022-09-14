<?php

namespace Gashmob\Mdgen;

final class EngineState
{
    const INIT = 0;
    const OLIST = 1;
    const ULIST = 2;
    const CODE = 3;
    const QUOTE = 4;
    const TABLE = 5;
    const TABLEH = 6;
    const TABLEB = 7;

    /**
     * @var int
     */
    public $state;
    /**
     * @var int
     */
    public $level;
    /**
     * @var string
     */
    public $table;
    /**
     * @var string[]
     */
    public $aligns;

    /**
     * @param int $state
     * @param int $level
     * @param string $table
     * @param string[] $aligns
     */
    public function __construct($state = self::INIT, $level = -1, $table = '', $aligns = [])
    {
        $this->state = $state;
        $this->level = $level;
        $this->table = $table;
        $this->aligns = $aligns;
    }
}