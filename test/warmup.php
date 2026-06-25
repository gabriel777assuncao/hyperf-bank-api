<?php

declare(strict_types=1);

use Hyperf\Di\ClassLoader;
use Hyperf\Engine\DefaultOption;

ini_set('display_errors', 'on');
error_reporting(E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

require BASE_PATH.'/vendor/autoload.php';

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', DefaultOption::hookFlags());

ClassLoader::init();