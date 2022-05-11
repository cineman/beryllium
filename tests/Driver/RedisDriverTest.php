<?php 

namespace Beryllium\Tests\Driver;

use Redis;
use Beryllium\Driver\DriverInterface;
use Beryllium\Driver\RedisDriver;
use Beryllium\Job;

class RedisDriverTest extends BaseDriverTest
{
    /**
	 * This method should return an instance of your driver.
     * The instance should optimally use a real backend as long as
     * no additional dependencies are requried.
     * 
     * All drivers are required to return the same result to pass the test case.
	 */
	protected function createDriverInstance() : DriverInterface
    {
        $redis = new Redis;
		$redis->pconnect('localhost');

		$driver = new RedisDriver($redis);
		$driver->setQueueKeyPrefix('test.beryllium.queue.');
		$driver->setLockKeyPrefix('test.beryllium.lock.');

		return $driver;
    }

    /**
     * Returns the string class name of your driver
     */
    protected function getDriverClassName() : string
    {
        return RedisDriver::class;
    }
}