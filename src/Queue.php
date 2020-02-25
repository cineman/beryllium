<?php 

namespace Beryllium;

use Beryllium\Driver\DriverInterface;

class Queue
{	
	/**
	 * The driver to read the queue
	 *
	 * @var DriverInterface
	 */
	protected $driver;

	/**
	 * Construct
	 *
	 * @param DriverInterface 			$driver
	 */
	public function __construct(DriverInterface $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * Retrieve the id of the next job or null if there is nothing todo
	 *
	 * @return string 
	 */
	public function getNextJobId() : ?string
	{
		return $this->driver->popWaitingId();
	}

	/**
	 * Get a specific job from the queue by id
	 *
	 * @param string 				$jobId
	 * @return Job
	 */
	public function get(string $jobId) : ?Job
	{
		return $this->driver->get($jobId);
	}

	/**
	 * Create a new job and enqueue it.
	 *
	 * @param string 			$action
	 * @param array 			$parameters
	 *
	 * @return Job Returns the job 
	 */
	public function add(string $action, array $parameters = []) : Job
	{
		$job = new Job(uniqid('', true), $action, $parameters);
		$this->driver->add($job);
		return $job;
	}

	/**
	 * Mark the given job as done
	 *
	 * @param string 			$jobId
	 * @return void
	 */
	public function done(string $jobId)
	{
		$this->driver->cleanup($jobId);
	}

	/** 
	 * Consider a retry of the job
	 *
	 * @param string 			$jobId
	 * @return bool
	 */
	public function considerRetry(string $jobId) : bool
	{
		$maxRetries = $this->driver->getMaxRetries($jobId);
		$attempts = $this->driver->attemptCount($jobId);

		// if one of the values has never been set we are unable to retry
		if ($maxRetries === -1 || $attempts === -1) {
			return false;
		}

		if ($maxRetries > $attempts) {
			$this->driver->retry($jobId); return true;
		}

		return false;
	}

	/**
	 * Store the current number of active workers
	 * This is used for debugging and maintanance
	 *
	 * @param int 			$num
	 * @return void
	 */
	public function statsSetActiveWorkers(int $num)
	{
		$this->driver->storeStatsValue('active_workers', $num);
	}

	/**
	 * Returns the number of jobs currently in queue
	 *
	 * @return int
	 */
	public function waitingCount() : int
	{
		return $this->driver->waitingCount();
	}
}
