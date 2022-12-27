<?php //== 2022-12-07 10:17:29 PM
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

chdir(__DIR__);
defined('GitLabAutoSync_CONFIGFILE') or define('GitLabAutoSync_CONFIGFILE', __DIR__ . '/GitLabAutoSync.config.php');
return require_once 'phar://GitLabAutoSync.phar';
