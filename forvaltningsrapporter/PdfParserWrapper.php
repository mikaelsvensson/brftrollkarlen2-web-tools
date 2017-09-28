<?php

class PdfParserWrapper
{
    /**
     * Shamelessly copied from the PdfParser class written by Sebastien MALOT <sebastien@malot.fr>
     */
    function extractTextElements($content)
    {
        $text = '';
        $lines = explode("\n", $content);

        $accX = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            $matches = array();

            // Parse each lines to extract command and operator values
            if (preg_match('/^(?<command>.*[\)\] ])(?<operator>[a-z]+[\*]?)$/i', $line, $matches)) {
                $command = trim($matches['command']);

                // Convert octal encoding
                $found_octal_values = array();
                preg_match_all('/\\\\([0-9]{3})/', $command, $found_octal_values);

                foreach ($found_octal_values[0] as $value) {
                    $octal = substr($value, 1);

                    if (intval($octal) < 40) {
                        // Skips non printable chars
                        $command = str_replace($value, '', $command);
                    } else {
                        $command = str_replace($value, chr(octdec($octal)), $command);
                    }
                }
                // Removes encoded new lines, tabs, ...
                $command = preg_replace('/\\\\[\r\n]/', '', $command);
                $command = preg_replace('/\\\\[rnftb ]/', ' ', $command);
                // Force UTF-8 charset
                $encoding = mb_detect_encoding($command, array('ASCII', 'UTF-8', 'Windows-1252', 'ISO-8859-1'));
                if (strtoupper($encoding) != 'UTF-8') {
                    if ($decoded = @iconv('CP1252', 'UTF-8//TRANSLIT//IGNORE', $command)) {
                        $command = $decoded;
                    }
                }
                // Removes leading spaces
                $operator = trim($matches['operator']);
            } else {
                $command = $line;
                $operator = '';
            }

            // Handle main operators
            if (strlen($operator) > 0) {
                $tagName = $operator;
                $tagAttrs = array();
                $tagContent = "";
                switch ($operator) {
                    // Set character spacing.
                    case 'Tc':
                        break;

                    // Move text current point.
                    case 'Td':
                        $tagAttrs['cmd'] = $command;
                        $values = explode(' ', $command);
                        $y = array_pop($values);
                        $x = array_pop($values);
                        if ($x > 0) {
                        }
                        if ($y < -1.0) {
                            // The Y position has changed by more than an insignificant amount. Assume this means that a new report row has been found.
                            $accX = 0;
                            $text .= '</row><row>';
                        } elseif ($y == 0) {
                            $accX += $x;
                            if ($x > 0) {
                            }
                        }
                        break;

                    // Move text current point and set leading.
                    case 'TD':
                        $values = explode(' ', $command);
                        $y = array_pop($values);
                        if ($y < 0) {
                        }
                        break;

                    // Set font name and size.
                    case 'Tf':
                        break;

                    // Display text, allowing individual character positioning
                    case 'TJ':
                        $start = mb_strpos($command, '[', null, 'UTF-8') + 1;
                        $end = mb_strrpos($command, ']', null, 'UTF-8');
                        $tagContent = htmlspecialchars(self::parseTextCommand(mb_substr($command, $start, $end - $start, 'UTF-8')));
                        break;

                    // Display text.
                    case 'Tj':
                        $start = mb_strpos($command, '(', null, 'UTF-8') + 1;
                        $end = mb_strrpos($command, ')', null, 'UTF-8');
                        $tagContent = htmlspecialchars(mb_substr($command, $start, $end - $start, 'UTF-8')); // Removes round brackets
                        break;

                    // Set leading.
                    case 'TL':

                        // Set text matrix.
                    case 'Tm':
                        break;

                    // Set text rendering mode.
                    case 'Tr':
                        break;

                    // Set super/subscripting text rise.
                    case 'Ts':
                        break;

                    // Set text spacing.
                    case 'Tw':
                        break;

                    // Set horizontal scaling.
                    case 'Tz':
                        break;

                    // Move to start of next line.
                    case 'T * ':
                        $tagContent = ' ';
                        break;

                    // Internal use
                    case 'g':
                    case 'gs':
                    case 're':
                    case 'f':
                        // Begin text
                    case 'BT':
                        // End text
                    case 'ET':
                        break;

                    case '':
                        break;

                    default:
                }
                $tagAttrs['x'] = $accX;
                $text .= sprintf("<%s%s>%s</%s>\n",
                    $tagName,
                    implode('',
                        array_map(function ($k, $v) {
                            return ' ' . $k . '="' . $v . '"';
                        }, array_keys($tagAttrs), $tagAttrs)),
                    $tagContent,
                    $tagName
                );
            }

        }
        return '<row>' . $text . '</row>';
    }

    /**
     * Shamelessly copied from the PdfParser class written by Sebastien MALOT <sebastien@malot.fr>
     * @param $text
     * @return string
     */
    function parseTextCommand($text)
    {

        $result = '';
        $cur_start_pos = 0;

        while (($cur_start_text = mb_strpos($text, '(', $cur_start_pos, 'UTF-8')) !== false) {
            // New text element found
            if ($cur_start_text - $cur_start_pos > 8) {
                $spacing = ' ';
            } else {
                $spacing_size = mb_substr($text, $cur_start_pos, $cur_start_text - $cur_start_pos, 'UTF-8');

                if ($spacing_size < -50) {
                    $spacing = ' ';
                } else {
                    $spacing = '';
                }
            }
            $cur_start_text++;

            $start_search_end = $cur_start_text;
            while (($cur_start_pos = mb_strpos($text, ')', $start_search_end, 'UTF-8')) !== false) {
                if (mb_substr($text, $cur_start_pos - 1, 1, 'UTF-8') != '\\') {
                    break;
                }
                $start_search_end = $cur_start_pos + 1;
            }

            // something wrong happened
            if ($cur_start_pos === false) {
                break;
            }

            // Add to result
            $result .= $spacing . mb_substr($text, $cur_start_text, $cur_start_pos - $cur_start_text, 'UTF-8');
            $cur_start_pos++;
        }

        return $result;
    }


    function pdfToXml($filename)
    {
        // Parse pdf file and build necessary objects.
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filename);

        // Retrieve all pages from the pdf file.
        $pages = $pdf->getPages();

        // Loop over each page to extract text.
        $content = "";
        foreach ($pages as $page) {
            $pageContents = $page->get('Contents');

            if ($pageContents instanceof \Smalot\PdfParser\Element\ElementArray) {

                foreach ($pageContents->getContent() as $pageContent) {
                    $content .= $pageContent->getContent() . "\n";
                }

            }
        }
        $content = '<doc>' . self::extractTextElements($content) . '</doc>';
        return $content;
    }
}

?>