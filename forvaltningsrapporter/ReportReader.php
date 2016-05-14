<?php
class ReportReader
{
    function create_filter_function($skipAptHeaders)
    {
        return function ($apt) use ($skipAptHeaders) {
            return count(array_intersect($skipAptHeaders, array_keys($apt))) == 0;
        };
    }

    function getReportObjects($cfg, $xml)
    {
        $apts = array();
        $apt = array();
        $field = 'ExtraInformation';
        $i = 0;

        $reader = null;
        foreach ($cfg->reader as $r) {
            $res = $xml->xpath($r['xpathMatchPattern']);
            if ($res !== false && count($res) > 0) {
                $reader = $r;
            }
        }
        if (!$reader) {
            return null;
        }
        $skipAptIfHeaderExists = explode(' ', "" . $reader['skipEntriesWithColumn']);

        $positionRules = $reader->positionRule;
        $rules = $reader->textRule;

        $followingOutputColumns = [];

        foreach ($xml->children() as $cmd) {
            $name = $cmd->getName();
            $value = trim($cmd);
            switch ($name) {
                case 'Tj':
                case 'TJ':
                    $x = array_shift($followingOutputColumns);
                    if ($x == null || strlen(trim($x)) == 0) {
                        foreach ($reader->textRule as $rule) {
                            list($key, $pattern, $new) = array("" . $rule['outputColumn'], "" . $rule['pattern'], $rule['isGroupStart'] == 'true');
                            if (preg_match("/$pattern/i", $value)) {
                                $field = $key;
                                if ($new) {
                                    $i++;
                                }
                                if ($rule['followingOutputColumns'] != null) {
                                    $followingOutputColumns = explode(' ', $rule['followingOutputColumns']);
                                }
                                break;
                            }
                        }
                    } else {
                        $field = $x;
                    }
                    $apts[$i][$field][] = "$value";
                    break;
                case 'Td':
                case 'Tm':
                    $field = 'ExtraInformation';
                    foreach ($reader->positionRule as $rule) {
                        list($key, $pos, $new) = array("" . $rule['outputColumn'], "" . $rule['exactMatch'], $rule['isGroupStart'] == 'true');
                        if ($cmd['cmd'] == $pos) {
                            $field = $key;
                            if ($new) {
                                $i++;
                            }
                            if ($rule['followingOutputColumns'] != null) {
                                $followingOutputColumns = explode(' ', "$field " . $rule['followingOutputColumns']);
                            }
                            break;
                        }
                    }
                    break;
            }
        }

        $apts = array_filter($apts, $this->create_filter_function($skipAptIfHeaderExists));
        return $apts;
    }
}