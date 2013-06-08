<?php
date_default_timezone_set('America/Los_Angeles');
define('HATHOORA_PROFILE_START_TIME', microtime());
define('HATHOORA_ROOTPATH', realpath(__DIR__ . '/..') .'/');
require_once HATHOORA_ROOTPATH .'/boot/autoload.php';

use hathoora\kernel;
$env = 'dev';

$kernel = new kernel($env);

if (php_sapi_name() == 'cli')
    $kernel->bootstrapCLI();
else
    $kernel->bootstrapWebPage();
