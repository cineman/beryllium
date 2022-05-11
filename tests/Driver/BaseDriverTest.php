<?php 

namespace Beryllium\Tests\Driver;

use Beryllium\Driver\DriverInterface;
use Beryllium\Job;

abstract class BaseDriverTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * This method should return an instance of your driver.
     * The instance should optimally use a real backend as long as
     * no additional dependencies are requried.
     * 
     * All drivers are required to return the same result to pass the test case.
	 */
	abstract protected function createDriverInstance() : DriverInterface;

    /**
     * Returns the string class name of your driver
     */
    abstract protected function getDriverClassName() : string;

	public function testConstruct()
	{
		$this->assertInstanceOf($this->getDriverClassName(), $this->createDriverInstance());
	}

	public function testAddAndGet()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data

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

	public function testpopWaitingId()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data

		// should be null because no jobs should be queued
		$this->assertNull($driver->popWaitingId());

		// enqueue some jobs
		$driver->add(new Job('test1', 'say', ['text' => 'hello']));
		$driver->add(new Job('test2', 'say', ['text' => 'world']));
		$driver->add(new Job('test3', 'say', ['text' => 'queue']));

		$job1 = $driver->get($driver->popWaitingId());
		$job2 = $driver->get($driver->popWaitingId());
		$job3 = $driver->get($driver->popWaitingId());

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

	public function testWaitingCount()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data

		$this->assertEquals(0, $driver->waitingCount());
		$driver->add(new Job('test1', 'say', ['text' => 'hello']));
		$this->assertEquals(1, $driver->waitingCount());
		$driver->add(new Job('test2', 'say', ['text' => 'world']));
		$this->assertEquals(2, $driver->waitingCount());

		$driver->cleanup($driver->popWaitingId());
		$this->assertEquals(1, $driver->waitingCount());
		$driver->cleanup($driver->popWaitingId());
		$this->assertEquals(0, $driver->waitingCount());
	}

	public function testRetry()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data

		// enqueue some jobs
		$driver->add(new Job('test1', 'say', ['text' => 'hello']));

		$this->assertEquals('test1', $driver->popWaitingId());
		$this->assertEquals(0, $driver->attemptCount('test1'));

		// queue should be empty now.
		$this->assertNull($driver->popWaitingId());

		// reinsert the job
		$driver->retry('test1');

		// job should appear again
		// number of attempts should have increased
		$this->assertEquals('test1', $driver->popWaitingId());
		$this->assertEquals(1, $driver->attemptCount('test1'));
	}

	public function testGetMaxRetries()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data
		
		$job = new Job('test1', 'say', ['text' => 'hello']);

		$this->assertEquals(-1, $driver->getMaxRetries($job->id()));
		$driver->add($job);
		$this->assertEquals(3, $driver->getMaxRetries($job->id()));

		$driver->cleanup($job->id());

		// custom number of retries		
		$this->assertEquals(-1, $driver->getMaxRetries($job->id()));
		$driver->add($job, 5);
		$this->assertEquals(5, $driver->getMaxRetries($job->id()));
	}
    
	public function testAttemptCount()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data
		
		$this->assertEquals(-1, $driver->attemptCount('unknown'));
		$this->assertEquals(-1, $driver->getMaxRetries('unknown'));

		$job = new Job('test1', 'say', ['text' => 'hello']);
		$driver->add($job);
		$this->assertEquals(0, $driver->attemptCount($job->id()));
		$driver->popWaitingId();
		$driver->retry($job->id());
		$this->assertEquals(1, $driver->attemptCount($job->id()));
	}

	public function testStatsStorage()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data
		
		foreach([
			42,
			true,
			'string',
			[1, 2, 3],
			['a' => 'b', 'c' => 'd'],
		] as $value)
		{
			$driver->storeStatsValue('stat', $value);
			$this->assertEquals($value, $driver->getStatsValue('stat'));
		}
	}

	public function testLocking()
	{
        $driver = $this->createDriverInstance();
		$driver->clearEverything(); // make sure to clear the data

		// should not be locked yet
		$this->assertFalse($driver->isLocked('test'));
		$this->assertNull($driver->getLockToken('test'));

		$this->assertTrue($driver->lock('test', 'a', 60));
		$this->assertFalse($driver->lock('test', 'b', 60));

		$this->assertTrue($driver->isLocked('test'));
		$this->assertEquals('a', $driver->getLockToken('test'));

		$this->assertFalse($driver->unlock('test', 'b'));
		$this->assertTrue($driver->unlock('test', 'a'));

		$this->assertFalse($driver->isLocked('test'));
		$this->assertTrue($driver->lock('test', 'b', 60));
		$this->assertTrue($driver->isLocked('test'));
		$this->assertEquals('b', $driver->getLockToken('test'));
		$this->assertTrue($driver->unlock('test', 'b'));
	}	
}
