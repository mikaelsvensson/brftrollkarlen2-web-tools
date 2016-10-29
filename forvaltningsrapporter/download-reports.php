<?php
const COOKIE_HEADER_START = "Set-Cookie: ";
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
        $ModDate = getModDate();

        $cookieHeader = getAuthCookieHeader($ModDate);

        $cookie = "Userid=" . STOFAST_USERNAME . ";" . strtok($cookieHeader, ";");
    }
    return $cookie;
}

function getAuthCookieHeader($ModDate)
{
    $params = array(
        "%%ModDate" => $ModDate,
        "RedirectTo" => "/ts/gosud.nsf/redirect_login?openagent",
        "Username" => STOFAST_USERNAME,
        "Password" => STOFAST_PASSWORD
    );

    $ch = curl_init('https://entre.stofast.se/names.nsf?Login');
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $c = curl_exec($ch);
    curl_close($ch);

    $pos = strpos($c, COOKIE_HEADER_START);
    $cookieValuePos = $pos + strlen(COOKIE_HEADER_START);
    $setCookieHeader = substr($c, $cookieValuePos, strpos($c, ";", $cookieValuePos) - $cookieValuePos);

    if (empty($setCookieHeader)) {
        die("Empty cookie");
    }
    return $setCookieHeader;
}

function getModDate()
{
    $ch1 = curl_init('https://entre.stofast.se/');
    curl_setopt($ch1, CURLOPT_HEADER, true);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    $c1 = curl_exec($ch1);
    curl_close($ch1);

    $match = [];
    preg_match('/[0-9A-F]{16}/', $c1, $match);
    $ModDate = $match[0];
    return $ModDate;
}

function downloadFileFromUrl($filename, $url)
{
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

$timestamp = date('Ymd');
$force = "true" == $_GET["force"];
$downloadReportsToday = in_array(substr($timestamp, -2), REPORT_DAYS);

if ($force || $downloadReportsToday) {
    mkdir(FILES_FOLDER, 0700, true);
    foreach ($REPORTS as $title => $reportCfg) {
        $url = $reportCfg['url'];
        if (empty($url)) {
            continue;
        }
        $filename = FILES_FOLDER . $title . '-' . $timestamp . '.pdf';
        $isReportDownloaded = file_exists($filename);
        if ($force || !$isReportDownloaded) {
            downloadFileFromUrl($filename, $url);
        }
    }
    echo "Done";
} else {
    echo "No reports will be downloaded today";
}
?>