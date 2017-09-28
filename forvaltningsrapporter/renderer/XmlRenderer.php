<?php
class XmlRenderer
{
    function writerDocStart()
    {
        header('Content-Type: text/xml');
        print '<groups>';
    }
    function write($apts)
    {
        foreach ($apts as $apt) {
            print '<group>';
            foreach ($apt as $key => $values) {
                printf('<%s>', $key);
                foreach ($values as $value) {
                    printf('<value>%s</value>', $value);
                }
                printf('</%s>', $key);
            }
            print '</group>';
        }
    }
    function writerDocEnd()
    {
        print '</groups>';
    }
}