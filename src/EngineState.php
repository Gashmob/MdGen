<?php

namespace Gashmob\Mdgen;

final class EngineState
{
    const STATE_INIT = 0;
    const ORD_LIST = 1;
    const UNORD_LIST = 2;
    const BLOCK_QUOTE = 3;
    const TABLE = 4;
    const HTML = 5;
    const TITLE = 6;

    private function __construct()
    {
    }
}