<?php 

namespace Beryllium\Tests;

use Redis;
use Beryllium\{Queue};
use Beryllium\Driver\RedisDriver;

class QueueTest extends \PHPUnit\Framework\TestCase
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
        $this->assertInstanceOf(Queue::class, $queue);
    }

    public function testStats()
    {
        static::ensureDriver();

        $queue = new Queue(static::$driver);

        $queue->statsSetActiveWorkers(4);
        $this->assertEquals(4, $queue->statsGetActiveWorkers());
        $this->assertEquals(4, $queue->statsGetValue('active_workers'));
        $queue->statsSetValue('active_workers', 8);
        $this->assertEquals(8, $queue->statsGetValue('active_workers'));
    }
}
