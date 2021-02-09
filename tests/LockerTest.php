<?php 

namespace Beryllium\Tests;

use Redis;
use Beryllium\{Locker, Mutex};
use Beryllium\Driver\RedisDriver;
use Beryllium\Exception\LockedMutexException;

class LockerTest extends \PHPUnit\Framework\TestCase
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

        $this->assertInstanceOf(Locker::class, new Locker(static::$driver));
    }

    public function testMutex()
    {
        static::ensureDriver();

        $locker = new Locker(static::$driver);
        $mutex = $locker->mutex('foo');

        $this->assertEquals('foo', $mutex->getMutexKey());

        $this->assertFalse($mutex->isLocked());
    }
}
