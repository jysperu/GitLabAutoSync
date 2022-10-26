<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

chdir(__DIR__);
defined('GitLabAutoSync_CONFIGFILE') or define('GitLabAutoSync_CONFIGFILE', __DIR__ . '/GitLabAutoSync.config.php');
return require_once 'phar://GitLabAutoSync.phar';
