<?php 

namespace Beryllium\Tests\Driver;

use Redis;
use Beryllium\Job;
use Beryllium\Driver\RedisDriver;

class RedisDriverTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Provides a redis driver instance
	 */
	public function redisDriverProvider()
	{
		$redis = new Redis;
		$redis->pconnect('localhost');

		$driver = new RedisDriver($redis);

		return [[$driver]];
	}

	/**
     * @dataProvider redisDriverProvider
     */
	public function testConstruct(RedisDriver $driver)
	{
		$this->assertInstanceOf(RedisDriver::class, $driver);
	}

	/**
     * @dataProvider redisDriverProvider
     */
	public function testAdd(RedisDriver $driver)
	{
		$job = new Job('test', 'say', ['text' => 'hello']);

		$driver->add($job);

		$job2 = $driver->get('test');

		$this->assertEquals($job->id(), $job2->id());
		$this->assertEquals($job->action(), $job2->action());
		$this->assertEquals($job->parameters(), $job2->parameters());

		// cleanup
		$driver->cleanup('test');

		$this->assertNull($driver->get('test'));
	}

	/**
     * @dataProvider redisDriverProvider
     */
	public function testGetWaitingId(RedisDriver $driver)
	{
		$driver->clearEverything(); // make sure to clear the data

		// should be null because no jobs should be queued
		$this->assertNull($driver->getWaitingId());

		// enqueue some jobs
		$driver->add(new Job('test1', 'say', ['text' => 'hello']));
		$driver->add(new Job('test2', 'say', ['text' => 'world']));
		$driver->add(new Job('test3', 'say', ['text' => 'queue']));

		$job1 = $driver->get($driver->getWaitingId());
		$job2 = $driver->get($driver->getWaitingId());
		$job3 = $driver->get($driver->getWaitingId());

		$this->assertEquals('test1', $job1->id());
		$this->assertEquals('test2', $job2->id());
		$this->assertEquals('test3', $job3->id());

		$this->assertEquals('say', $job1->action());
		$this->assertEquals('say', $job2->action());
		$this->assertEquals('say', $job3->action());

		$this->assertEquals('hello', $job1->parameter('text'));
		$this->assertEquals('world', $job2->parameter('text'));
		$this->assertEquals('queue', $job3->parameter('text'));
	}
}