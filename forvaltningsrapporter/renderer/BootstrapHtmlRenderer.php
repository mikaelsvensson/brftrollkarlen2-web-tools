<?php

class BootstrapHtmlRenderer
{
    function writerDocStart()
    {
    }

    function write($apts)
    {
        $id = uniqid();
        $exportData = [];
        print <<< HTML_START
        <table class="table table-striped table-hover table-condensed" id="table-$id">
        <thead>
        <tr>
HTML_START;

        $headers = array();
        foreach ($apts as $apt) {
            $headers = array_merge($headers, array_keys($apt));
        }
        $headers = array_unique($headers);

        $stats = [];
        $exportRow = [];
        foreach ($headers as $header) {
            $exportRow[] = $header;
            print "<th>$header</th>";
        }
        $exportData[] = $exportRow;

        print <<< HTML_START
        </tr>
        </thead>
        <tfoot>
        <tr>
HTML_START;
        foreach ($apts as $apt) {
            foreach ($headers as $header) {
                if (@is_array($apt[$header])) {
                    $value = join(',', $apt[$header]);
                    @$stats[$header][$value]++;
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
            $exportRow = [];
            print '<tr class="entry">';
            foreach ($headers as $header) {
                $value = @is_array($apt[$header]) ? join(',', $apt[$header]) : '';
                printf('<td class="%s">%s</td>', $header, $value);
                $exportRow[] = strip_tags($value);
            }
            print "</tr>";
            $exportData[] = $exportRow;
        }

        print <<< HTML_END
        </tbody>
        </table>
HTML_END;
        printf('<input type="hidden" id="table-%s-exporter-data" value="%s" name="exporter-data">', $id, base64_encode(serialize($exportData)));
    }

    function writerDocEnd()
    {
        print <<< HTML_END
        </body>
        </html>
HTML_END;
    }
}