<?php
define('PHPREDIS_ADMIN_PATH', dirname(__DIR__));

// Undo magic quotes (both in keys and values)
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
  $process = array(&$_GET, &$_POST);

  while (list($key, $val) = each($process)) {
    foreach ($val as $k => $v) {
      unset($process[$key][$k]);

      if (is_array($v)) {
        $process[$key][stripslashes($k)] = $v;
        $process[] = &$process[$key][stripslashes($k)];
      } else {
        $process[$key][stripslashes($k)] = stripslashes($v);
      }
    }
  }

  unset($process);
}




// These includes are needed by each script.
require_once PHPREDIS_ADMIN_PATH . '/includes/config.inc.php';
require_once PHPREDIS_ADMIN_PATH . '/includes/functions.inc.php';
require_once PHPREDIS_ADMIN_PATH . '/includes/page.inc.php';


if (isset($config['login'])) {
  require_once PHPREDIS_ADMIN_PATH . '/includes/login.inc.php';
}




if (isset($login['servers'])) {
  $i = current($login['servers']);
} else {
  $i = 0;
}


$action = null;
$parentKey = null;

if (!empty($_GET['action']) && !empty($_GET['key'])) {
    $action = $_GET['action'];
    $parentKey = urldecode($_GET['key']);
}

if (isset($_GET['s']) && is_numeric($_GET['s']) && ($_GET['s'] < count($config['servers']))) {
  $i = $_GET['s'];
}

$server       = $config['servers'][$i];
$server['id'] = $i;


if (isset($login, $login['servers'])) {
  if (array_search($i, $login['servers']) === false) {
    die('You are not allowed to access this database.');
  }

  foreach ($config['servers'] as $key => $ignore) {
    if (array_search($key, $login['servers']) === false) {
      unset($config['servers'][$key]);
    }
  }
}


if (!isset($server['db'])) {
  $server['db'] = 0;
}

if (!isset($server['filter'])) {
  $server['filter'] = '*';
}

// Setup a connection to Redis.
$redis = new Redis();
$redis->connect($server['host'], $server['port']);

if (isset($server['auth'])) {
  if (!$redis->auth($server['auth'])) {
    die('ERROR: Authentication failed ('.$server['host'].':'.$server['port'].')');
  }
}


if ($server['db'] != 0) {
  if (!$redis->select($server['db'])) {
    die('ERROR: Selecting database failed ('.$server['host'].':'.$server['port'].','.$server['db'].')');
  }
}

error_reporting(1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '500M');


$types = array(
    Redis::REDIS_ZSET   => 'zset',
    Redis::REDIS_HASH   => 'hash',
    Redis::REDIS_LIST   => 'list',
    Redis::REDIS_SET    => 'set',
    Redis::REDIS_STRING => 'string'
);

?>
