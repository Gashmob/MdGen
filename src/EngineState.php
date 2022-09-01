<?php

namespace Gashmob\Mdgen;

final class EngineState
{
    public static $STATE_INIT = 0;
    public static $ORD_LIST = 1;
    public static $UNORD_LIST = 2;
    public static $BLOCK_QUOTE = 3;
    public static $TABLE = 4;
    public static $HTML = 5;

    private function __construct()
    {
    }
}