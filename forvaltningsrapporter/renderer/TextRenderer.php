<?php
//namespace report\renderer;

class TextRenderer
{
    function writerDocStart()
    {
    }
    function write($apts)
    {
        header('Content-Type: text/plain');
        $headers = array();
        foreach ($apts as $apt) {
            $headers = array_merge($headers, array_keys($apt));
        }
        $headers = array_unique($headers);

        foreach ($headers as $header) {
            print "$header\t";
        }
        print "\n";
        foreach ($apts as $apt) {
            foreach ($headers as $header) {
                $value = join(',', $apt[$header]);
                print "$value\t";
            }
            print "\n";
        }

    }
    function writerDocEnd()
    {
    }
}