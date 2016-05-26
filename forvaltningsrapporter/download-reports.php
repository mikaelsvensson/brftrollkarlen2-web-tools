<?php
require_once 'renderer/HtmlRenderer.php';
require_once 'ReportReader.php';
require_once 'config.php';
require_once 'config-credentials.php';
require_once '../lib/PdfParser.php';

$cookie = null;

function getAuthCookie()
{
    global $cookie;
    if (!isset($cookie)) {
        $params = array(
            "%%ModDate" => time(),
            "RedirectTo" => "/ts/gosud.nsf/redirect_login?openagent",
            "Username" => STOFAST_USERNAME,
            "Password" => STOFAST_PASSWORD
        );
        $postData = "";
        foreach ($params as $k => $v) {
            $postData .= $k . '=' . $v . '&';
        }
        $postData = rtrim($postData, '&');

        $ch = curl_init('https://entre.stofast.se/names.nsf?Login');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, count($postData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $c = curl_exec($ch);

        curl_close($ch);

        list($headersRaw, $body) = explode("\r\n\r\n", $c, 2);

        $headers = array();
        foreach (explode("\n", $headersRaw) as $line) {
            list($name, $value) = explode(":", $line);
            $headers[trim($name)] = trim($value);
        }

        $setCookieHeader = $headers['Set-Cookie'];
        $cookie = "Userid=" . USERNAME . "," . strtok($setCookieHeader, ";");
    }
    return $cookie;
}

$timestamp = date('Ymd');
$downloadReportsToday = in_array(substr($timestamp, -2), REPORT_DAYS);
if ($downloadReportsToday) {
    $renderer = new HtmlRenderer();
    $rendererCfg = simplexml_load_file("cfg.xml");
    mkdir(FILES_FOLDER, 0700, true);
    foreach ($REPORTS as $title => $reportCfg) {
        $url = $reportCfg['url'];
        $filename = FILES_FOLDER . $title . '-' . $timestamp . '.pdf';
        $isReportDownloaded = file_exists($filename);
        if (!$isReportDownloaded) {
            $fp = fopen($filename, 'wb');
            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_HTTPHEADER, array("Cookie: " . getAuthCookie()));
            curl_setopt($handle, CURLOPT_FILE, $fp);
            curl_setopt($handle, CURLOPT_HEADER, false);
            curl_exec($handle);
            print_r(curl_getinfo($handle));
            curl_close($handle);
            fclose($fp);
        }
    }
    echo "Done";
} else {
    echo "No reports will be downloaded today";
}
?>