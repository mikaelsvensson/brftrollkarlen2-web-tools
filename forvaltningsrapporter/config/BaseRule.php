<?php

namespace Config;

class BaseRule
{
    public $outputColumn;
    public $isGroupStart;
    public $followingOutputColumns = [];

    public function __construct($outputColumn, $isGroupStart, array $followingOutputColumns)
    {
        if (gettype($isGroupStart) != 'boolean') {
            die("Incorrect datatype for $outputColumn");
        }
        $this->outputColumn = $outputColumn;
        $this->isGroupStart = $isGroupStart;
        $this->followingOutputColumns = $followingOutputColumns;
    }


}