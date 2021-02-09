<?php 

namespace Beryllium\Tests;

use Redis;
use Beryllium\{ProcessManager, Queue};
use Beryllium\Driver\RedisDriver;

class ProcessManagerTest extends \PHPUnit\Framework\TestCase
{
    private static $driver = null;

    private function ensureDriver()
    {
        if (!static::$driver) {

            $redis = new Redis;
            $redis->pconnect('localhost');

            static::$driver = new RedisDriver($redis);
            static::$driver->setQueueKeyPrefix('test.beryllium.queue.');
            static::$driver->setLockKeyPrefix('test.beryllium.lock.');
        }

        static::$driver->clearEverything();

        return static::$driver;
    }

    public function testConstruct()
    {
        static::ensureDriver();

        $queue = new Queue(static::$driver);
        $pm = new ProcessManager($queue, 'test');

        $this->assertInstanceOf(ProcessManager::class, $pm);
    }

    public function testSettings()
    {
        static::ensureDriver();

        $queue = new Queue(static::$driver);
        $pm = new ProcessManager($queue, 'test');

        $pm->setIdleWait(20000);
        $this->assertEquals(20000, $pm->getIdleWait());

        $pm->setMaxWorkers(8);
        $this->assertEquals(8, $pm->getMaxWorkers());
    }
}
