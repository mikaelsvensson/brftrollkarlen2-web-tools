<?php

namespace Config;


class PositionRule extends BaseRule
{
    public $exactMatch;

    public function __construct($outputColumn, $exactMatch, $isGroupStart = false, array $followingOutputColumns = [])
    {
        parent::__construct($outputColumn, $isGroupStart, $followingOutputColumns);
        $this->exactMatch = $exactMatch;
    }

}