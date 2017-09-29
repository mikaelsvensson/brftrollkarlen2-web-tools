<?php
// Include Composer autoloader if not already done.
include '../vendor/autoload.php';

require_once 'config.php';

$cfg = parse_ini_file("config.ini", true);

session_start();

function createGoogleClient()
{
    global $cfg;

    $client = new Google_Client();
    $client->setAuthConfig($cfg['google']['client_secret_file']);
    $client->setRedirectUri($cfg['google']['oauthcallback_uri']);
    //$client->addScope(Google_Service_Drive::DRIVE_METADATA);
    //TODO: Why do we need the scope Google_Service_Drive::DRIVE?
    $client->addScope(Google_Service_Drive::DRIVE);
    //TODO: Why do we need the scope Google_Service_Plus::USERINFO_EMAIL?
    $client->addScope(Google_Service_Plus::USERINFO_EMAIL);
    $client->addScope("http://www.google.com/m8/feeds/");
    return $client;
}
?>