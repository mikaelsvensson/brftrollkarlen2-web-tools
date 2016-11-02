<?php

//namespace report\renderer;

class BootstrapHtmlRenderer
{
    function writerDocStart()
    {
    }

    function write($apts)
    {
        print <<< HTML_START
        <table class="table table-striped table-hover table-condensed">
        <thead>
        <tr>
HTML_START;

        $headers = array();
        foreach ($apts as $apt) {
            $headers = array_merge($headers, array_keys($apt));
        }
        $headers = array_unique($headers);

        $stats = [];
        foreach ($headers as $header) {
            print "<th>$header</th>";
        }

        print <<< HTML_START
        </tr>
        </thead>
        <tfoot>
        <tr>
HTML_START;
        foreach ($apts as $apt) {
            foreach ($headers as $header) {
                if (is_array($apt[$header])) {
                    $value = join(',', $apt[$header]);
                    $stats[$header][$value]++;
                }
            }
        }

        foreach ($headers as $header) {
            print '<td><ul class="list-unstyled">';
            arsort($stats[$header]);
            $slice = count($stats[$header]) > 10;
            if ($slice) {
                $stats[$header] = array_slice($stats[$header], 0, 9);
            }
            foreach ($stats[$header] as $value => $count) {
                if ($count > 1 && !empty($value)) {
                    printf('<li><small>%s: %s&nbsp;ggr</small></li>', $value, $count);
                }
            }
            if ($slice) {
                print '<li><small>...</small></li>';
            }
            print "</ul></td>";
        }

        print <<< HTML_START
        </tr>
        </tfoot>
        <tbody>
HTML_START;


        foreach ($apts as $apt) {
            print '<tr class="entry">';
            foreach ($headers as $header) {
                $value = is_array($apt[$header]) ? join(',', $apt[$header]) : '';
                printf('<td class="%s">%s</td>', $header, $value);
            }
            print "</tr>";
        }

        print <<< HTML_END
        </tbody>
        </table>
HTML_END;
    }

    function writerDocEnd()
    {
        print <<< HTML_END
        </body>
        </html>
HTML_END;
    }
}