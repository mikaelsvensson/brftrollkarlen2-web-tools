<?php

//namespace report\renderer;

class HtmlRenderer
{
    function writerDocStart()
    {
        $time = time();
        header('Content-Type: text/html');
        print <<< HTML_START
        <!doctype html>
        <html>
        <head>
        <meta charset="windows-1252">
        <style>
        body {
        font-family: sans-serif;
        }
        td { border: 1px solid #555; }
        td, th { text-align: left; padding: 0.2em; vertical-align: top;}
        td div { white-space: nowrap; }
        table { border-collapse: collapse; }
        h1 { font-size: 3em; font-weight: normal; }
        h2 { font-size: 1.5em; font-weight: normal; }
        h2 span{ font-size: 80%; color: #888; padding-left: 1em; font-style: italic; }
        div.diff { margin-left: 2em; font-size: 90% }
        tr.popular td { font-size: 90%; color: #888 }
        tr.popular ul { list-style-type: none; margin: 0; padding: 0}
        tr.entry:hover { background-color: #ddd; }
        </style>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.3/jquery.min.js"></script>
        </head>
        <body>
HTML_START;
    }

    function write($apts)
    {
        print <<< HTML_START
        <table>
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
        <tbody>
HTML_START;

        foreach ($apts as $apt) {
            print '<tr class="entry">';
            foreach ($headers as $header) {
                $value = join(',', $apt[$header]);
                printf('<td class="%s">%s</td>', $header, $value);
                $stats[$header][$value]++;
            }
            print "</tr>";
        }
        print '<tr class="popular">';
        foreach ($headers as $header) {
            print "<td><ul>";
            arsort($stats[$header]);
            $slice = count($stats[$header]) > 10;
            if ($slice) {
                $stats[$header] = array_slice($stats[$header], 0, 9);
            }
            foreach ($stats[$header] as $value => $count) {
                printf('<li>%s: %s</li>', $value, $count);
            }
            if ($slice) {
                print '<li>...</li>';
            }
            print "</ul></td>";
        }
        print "</tr>";

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