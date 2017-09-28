<?php
require_once 'config.php';
//TODO: Use Composer instead of "lib" folder
require_once '../lib/google-api-php-client-1-master/src/Google/autoload.php';

$cfg = parse_ini_file("config.ini", true);

//TODO: Why is set_include_path needed here?
set_include_path(get_include_path() . PATH_SEPARATOR . '../lib/google-api-php-client-1-master/src');

session_start();

function createGoogleClient()
{
    global $cfg;

    $client = new Google_Client();
    $client->setAuthConfigFile($cfg['google']['client_secret_file']);
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