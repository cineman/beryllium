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
use Beryllium\Locker;
use Beryllium\Driver\RedisDriver;

/**
 * Redis hostname and port by define
 */
if (!defined('BERYLLIUM_REDIS_HOST')) define('BERYLLIUM_REDIS_HOST', getenv('BERYLLIUM_REDIS_HOST'));
if (!defined('BERYLLIUM_REDIS_PORT')) define('BERYLLIUM_REDIS_PORT', getenv('BERYLLIUM_REDIS_PORT'));
if (!defined('BERYLLIUM_IDLE_WAIT')) define('BERYLLIUM_IDLE_WAIT', getenv('BERYLLIUM_IDLE_WAIT'));
if (!defined('BERYLLIUM_MAX_WORKERS')) define('BERYLLIUM_MAX_WORKERS', getenv('BERYLLIUM_MAX_WORKERS'));

/**
 * Create a queue instance with the default configuration
 */
$redis = new Redis();
$redis->pconnect(BERYLLIUM_REDIS_HOST, BERYLLIUM_REDIS_PORT);

// redis driver
$redisDriver = new RedisDriver($redis);
$redisDriver->setQueueKeyPrefix('beryllium.test.queue.');
$redisDriver->setLockKeyPrefix('beryllium.test.lock.');

/**
 * Demo function to build proof of work
 */
function proof_of_work(string $key, int $difficulty = 1) : int
{
    $it = 0;
    $p = str_repeat('0', $difficulty);

    do {
        $hash = md5($key . $it);
        $it++;
    } while(substr($hash, 0, $difficulty) !== $p);

    return $it;
}

/**
 * Return resources
 */
return [
    new Queue($redisDriver),
    new Locker($redisDriver),
    $redis
];
