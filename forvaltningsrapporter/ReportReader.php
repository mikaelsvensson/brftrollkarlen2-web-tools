<?php
use Config\PositionRule;
use Config\TextRule;

require_once 'config.php';

class ReportReader
{

    public function __construct($reportReader)
    {
        $this->reportReader = $reportReader;
    }

    function create_filter_function($skipAptHeaders)
    {
        return function ($apt) use ($skipAptHeaders) {
            return count(array_intersect($skipAptHeaders, array_keys($apt))) == 0;
        };
    }

    function getReportObjects($xml, $contacts = [])
    {
        global $REPORTS;

        $apts = array();
        $field = 'ExtraInformation';
        $i = 0;

        $reader = $this->reportReader;
        $skipAptIfHeaderExists = $reader->skipEntriesWithColumn;
        $sortColumnsByPosition = $reader->sortColumnsByPosition;

        $followingOutputColumns = [];

        $rows = $xml->children();
        foreach ($rows as $row) {

            $rowItems = null;
            if ($sortColumnsByPosition) {
                $i++;
                $rowItems = array();
                foreach ($row->children() as $child) {
                    $rowItems[] = $child;
                }
                usort($rowItems, function ($a, $b) {
                    if (floatval($a['x']) == floatval($b['x'])) {
                        // Sort by command name for when two commands share the same X coordiate. This is not ideal since it may change the original order but without this additional sort condition the sort order was unpredicatable, so it's better with a predictable order then unknown order.
                        return strcmp($a->getName(), $b->getName());
                    } else {
                        return floatval($a['x']) - floatval($b['x']);
                    }
                });
            } else {
                $rowItems = $row->children();
            }

            foreach ($rowItems as $cmd) {
                $name = $cmd->getName();
                $value = trim($cmd);
                switch ($name) {
                    case 'Tj':
                    case 'TJ':
                        $x = array_shift($followingOutputColumns);
                        if ($x == null || strlen(trim($x)) == 0) {
                            foreach ($reader->rules as $rule) {
                                if ($rule instanceof TextRule) {
                                    list($key, $pattern, $new) = array("" . $rule->outputColumn, "" . $rule->pattern, $rule->isGroupStart);
                                    if (preg_match("/$pattern/i", $value)) {
                                        $field = $key;
                                        if ($new) {
                                            $i++;
                                        }
                                        if ($rule->followingOutputColumns != null) {
                                            $followingOutputColumns = $rule->followingOutputColumns;
                                        }
                                        break;
                                    }
                                }
                            }
                        } else {
                            $field = $x;
                        }
                        $apts[$i][$field][] = "$value";
                        break;
                    case 'Td':
                    case 'Tm':
                        $commandAttr = (string)$cmd['cmd'];
                        $field = 'ExtraInformation';
                        foreach ($reader->rules as $rule) {
                            if ($rule instanceof PositionRule) {
                                list($key, $pos, $new) = array("" . $rule->outputColumn, "" . $rule->exactMatch, $rule->isGroupStart);
                                if ($commandAttr != '' && $commandAttr == $pos) {
                                    $field = $key;
                                    if ($new) {
                                        $i++;
                                    }
                                    if ($rule->followingOutputColumns != null) {
                                        $followingOutputColumns = array_merge([$field], $rule->followingOutputColumns);
                                    }
                                    break;
                                }
                            }
                        }
                        break;
                }
            }
        }

        $apts = array_filter($apts, $this->create_filter_function($skipAptIfHeaderExists));

        //TODO: Do not use $reader->id since it means that $reader->id needs to duplicate the key in $REPORTS
        $title = "" . $reader->id;
        $reportCfg = $REPORTS[$title];
        // Configuration specifies columns in array. Filtering function should use array values as keys.

        if ($reportCfg->getRowProcessor() != null) {
            $rowprocessor = $reportCfg->getRowProcessor();
            $fn = function ($apt) use ($contacts, $rowprocessor) {
                return $rowprocessor($apt, $contacts);
            };
            $apts = array_map($fn, $apts);
        }
        if ($reportCfg->getColumns() != null) {
            $columns = array_fill_keys($reportCfg->getColumns(), null);
            $apts = array_map(function ($obj) use ($columns) {
                return array_intersect_key($obj, $columns);
            }, $apts);
        }

        return $apts;
    }

}