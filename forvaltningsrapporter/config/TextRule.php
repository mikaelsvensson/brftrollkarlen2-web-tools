<?php

namespace Config;


class TextRule extends BaseRule
{
    public $pattern;

    public function __construct($outputColumn, $pattern, $isGroupStart = false, array $followingOutputColumns = [])
    {
        parent::__construct($outputColumn, $isGroupStart, $followingOutputColumns);
        $this->pattern = $pattern;
    }

}