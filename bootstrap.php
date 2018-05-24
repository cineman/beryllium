<?php if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

/**
 *---------------------------------------------------------------
 * Autoloader / Compser
 *---------------------------------------------------------------
 *
 * We need to access our dependencies & autloader..
 */
require __DIR__ . DS . 'vendor' . DS . 'autoload.php';

use Beryllium\Queue;
use Beryllium\Driver\RedisDriver;

/**
 * Redis hostname and port by define
 */
if (!defined('BERYLLIUM_REDIS_HOST')) define('BERYLLIUM_REDIS_HOST', '127.0.0.1');
if (!defined('BERYLLIUM_REDIS_PORT')) define('BERYLLIUM_REDIS_PORT', 6379);

/**
 * Create a queue instance with the default configuration
 */
$redis = new Redis();
$redis->pconnect(BERYLLIUM_REDIS_HOST, BERYLLIUM_REDIS_PORT);

return new Queue(new RedisDriver($redis));