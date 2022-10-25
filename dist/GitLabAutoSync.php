<?php
chdir(__DIR__);
defined('GitLabAutoSync_CONFIGFILE') or define('GitLabAutoSync_CONFIGFILE', __DIR__ . '/GitLabAutoSync.config.php');
return require_once 'phar://GitLabAutoSync.phar.gz';
