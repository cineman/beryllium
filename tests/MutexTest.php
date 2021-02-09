<?php 

namespace Beryllium\Tests;

use Redis;
use Beryllium\Mutex;
use Beryllium\Driver\RedisDriver;
use Beryllium\Exception\LockedMutexException;

class MutexTest extends \PHPUnit\Framework\TestCase
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

    /**
     * Create mutex for tests
     */
    public function createMutex(string $key)
    {
        return new Mutex(static::$driver, $key);
    }

    public function testConstruct()
    {
        static::ensureDriver();

        $mutex = $this->createMutex('test');

        $this->assertInstanceOf(Mutex::class, $mutex);

        $this->assertEquals('test', $mutex->getMutexKey());
    }

    public function testLockingSync()
    {
        static::ensureDriver();

        $mutex = $this->createMutex('test');
        $this->assertFalse($mutex->isLocked());
        $mutex->lock();
        $this->assertTrue($mutex->isLocked());
        $mutex->unlock();
        $this->assertFalse($mutex->isLocked());
    }

    public function testLockingSyncAlreadyLocked()
    {
        static::ensureDriver();
        
        $this->expectException(LockedMutexException::class);

        $mutex1 = $this->createMutex('test');
        $mutex2 = $this->createMutex('test');
        $this->assertFalse($mutex1->isLocked());
        $this->assertFalse($mutex2->isLocked());

        $mutex1->lock();
        $mutex2->lock();
    }

    public function testLockingSyncUnlock()
    {
        static::ensureDriver();

        $mutex1 = $this->createMutex('test');
        $mutex2 = $this->createMutex('test');

        $mutex1->lock();
        $this->assertTrue($mutex1->isLocked());

        try {
            $mutex2->lock();
        } catch(LockedMutexException $e) {}

        $this->assertTrue($mutex1->isLocked());
        $this->assertTrue($mutex2->isLocked());

        $this->assertTrue($mutex1->ownsLock());
        $this->assertFalse($mutex2->ownsLock());

        // unlock mutex 1 and lock mutex 2
        $mutex1->unlock();

        $this->assertFalse($mutex1->isLocked());
        $this->assertFalse($mutex2->isLocked());

        $mutex2->lock();

        $this->assertTrue($mutex1->isLocked());
        $this->assertTrue($mutex2->isLocked());

        $this->assertFalse($mutex1->ownsLock());
        $this->assertTrue($mutex2->ownsLock());
    }

    public function testSafeExec()
    {
        static::ensureDriver();

        $val = 0;

        $mutex1 = $this->createMutex('test');
        $mutex2 = $this->createMutex('test');

        $mutex1->safeExec(function() use(&$val) 
        {
            $val = 1;
        });

        $this->assertEquals(1, $val);

        $mutex1->safeExec(function() use(&$val) 
        {
            $val = 2;
        });

        $this->assertEquals(2, $val);

        try 
        {
            $mutex1->safeExec(function() use(&$val, $mutex2) 
            {
                $mutex2->safeExec(function() use(&$val) 
                {
                    $val = 3;
                });
            });
        }
        catch(LockedMutexException $e) {}

        $this->assertEquals(2, $val);
    }

    public function testSafeExecAlreadyLocked()
    {
        $mutex1 = $this->createMutex('test');
        $mutex2 = $this->createMutex('test');

        $this->expectException(LockedMutexException::class);

        $mutex1->safeExec(function() use($mutex2) 
        {
            $mutex2->safeExec(function()
            {
                
            });
        });
    }
}
