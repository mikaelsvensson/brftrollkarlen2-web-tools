<?php
const COOKIE_HEADER_START = "Set-Cookie: ";

// Include Composer autoloader if not already done.
include '../vendor/autoload.php';

require_once 'renderer/HtmlRenderer.php';
require_once 'ReportReader.php';
require_once 'config.php';
require_once 'config-credentials.php';
require_once '../lib/PdfParser.php';

//TODO: How is the execution of this script scheduled since One.com does not provide a scheduler (right?). Are we using some king of public "web ping service" pointed to this script?

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
    $contentType = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    curl_close($handle);
    fclose($fp);
    return $contentType;
}

$timestamp = date('Ymd');
$force = "true" == $_GET["force"];
$downloadReportsToday = in_array(substr($timestamp, -2), REPORT_DAYS);

function sendMail($subject, $template, $templateProperties)
{
    $body = utf8_decode(file_get_contents($template));
    $body = str_replace(array_keys($templateProperties), array_values($templateProperties), $body);
    $additionalHeaders = implode("\r\n", array("From: info@trollkarlen2.se", "Content-Type: text/plain; charset=UTF-8"));
    mail(
        MAIL_TO,
        $subject,
        utf8_encode($body),
        $additionalHeaders);
}

$savedReports = [];
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
            $contentType = downloadFileFromUrl($filename, $url);
            if ($contentType == 'application/pdf') {
                $savedReports[] = $filename;
                if (isset($reportCfg['afterdownloadprocessor'])) {
                    // The after-download processor can, for example, be used to split PDFs after downloading them.
                    $afterDownloadProcessor = $reportCfg['afterdownloadprocessor'];
                    $afterDownloadProcessor($filename);
                }
            } else {
                echo "Got $contentType instead of application/pdf";
                $props = array(
                    'SCRIPT_PATH' => $_SERVER['PHP_SELF'],
                    'FILE_PATH' => $filename,
                    'CONTENT_TYPE' => $contentType,
                    'STOFAST_USERNAME' => STOFAST_USERNAME
                );
                sendMail("[Forvaltningsrapporter] Kunde inte ladda ner rapport", "download-report-mail-contenttypeerror.utf8.txt", $props);
                break;
            }
        }
    }
    if (count($savedReports) > 0) {
        sendMail("[Forvaltningsrapporter] Rapporter nedladdade",
            "download-report-mail-savedreports.utf8.txt",
            array("REPORTS" => implode("\n - ", $savedReports)));
    }
    echo "Done";
} else {
    echo "No reports will be downloaded today";
}
?>