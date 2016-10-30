<?php

namespace Config;

class Reader
{
    public $id;
    public $xpathMatchPattern;
    public $skipEntriesWithColumn = [];
    public $sortColumnsByPosition = false;
    public $rules = [];

    public function __construct($id, $xpathMatchPattern, array $skipEntriesWithColumn, $sortColumnsByPosition, array $rules)
    {
        $this->id = $id;
        $this->xpathMatchPattern = $xpathMatchPattern;
        $this->skipEntriesWithColumn = $skipEntriesWithColumn;
        $this->sortColumnsByPosition = $sortColumnsByPosition;
        $this->rules = $rules;
    }

}