<?php 

namespace Beryllium\Driver;

use Redis;
use Beryllium\Job;

class RedisDriver implements DriverInterface
{	
	/** 
	 * Get the redis connection
	 *
	 * @var Redis
	 */
	protected $redis;

	/**
	 * The storage prefix
	 */
	const PREFIX = 'queue.';
	const WAITLIST = 'waitlist';
	const ATTEMPT = 'attempt.';
	const MAX_RETRIES = 'max_retries.';
	const DATA = 'data.';
	const STATS = 'stats.';

	/**
	 * Construct
	 *
	 * @param Redis 			$redis 
	 */
	public function __construct(Redis $redis)
	{
		$this->redis = $redis;
	}

	/**
	 * Add a job to the queue
	 *
	 * @param Job 			$job
	 * @param int 			$maxRetries
	 */
	public function add(Job $job, int $maxRetries = 3)
	{
		$id = $job->id(); // get the job id

		$this->redis->lPush(static::PREFIX . static::WAITLIST, $id);

		$this->redis->set(static::PREFIX . static::ATTEMPT . $id, 0);
		$this->redis->set(static::PREFIX . static::MAX_RETRIES . $id, $maxRetries);
		$this->redis->set(static::PREFIX . static::DATA . $id, $job->serialize());

		// timeout the queue elements after an hour 
		// if there is an error somewhere this way we at least clear the garbage
		$this->redis->expire(static::PREFIX . static::ATTEMPT . $id, 3600);
		$this->redis->expire(static::PREFIX . static::MAX_RETRIES . $id, 3600);
		$this->redis->expire(static::PREFIX . static::DATA . $id, 3600);
	}

	/**
	 * Get a job by id
	 *
	 * @param string 			$id
	 * @return Job
	 */
	public function get(string $id) : ?Job
	{
		// get the data
		if (!$data = $this->redis->get(static::PREFIX . static::DATA . $id)) {
			return null;
		}

		// unserialize it
		return Job::unserialize($data);
	}

	/**
	 * Get the ID of a waiting job
	 *
	 * @return string
	 */
	public function popWaitingId() : ?string
	{
		return $this->redis->rPop(static::PREFIX . static::WAITLIST) ?: null;
	}

	/**
	 * Counts the number of jobs waiting for execution
	 *
	 * @return int
	 */
	public function waitingCount() : int
	{
		return $this->redis->lLen(static::PREFIX . static::WAITLIST);
	}

	/**
	 * Reinsert the job into the waitlist
	 *
	 * @param string 			$id
	 * @return void
	 */
	public function retry(string $id)
	{
		$this->redis->incr(static::PREFIX . static::ATTEMPT . $id);
		$this->redis->lPush(static::PREFIX . static::WAITLIST, $id);
	}

	/**
	 * Get the maximum number of attempts we should try for the job
	 *
	 * @param string 				$id
	 * @return int
	 */
	public function getMaxRetries(string $id) : int
	{
		if (($c = $this->redis->get(static::PREFIX . static::MAX_RETRIES . $id)) !== false) return $c;
		return -1;
	}

	/**
	 * Get the number of attempts for the job
	 *
	 * @param string 			$id
	 * @return int
	 */
	public function attemptCount(string $id) : int
	{
		if (($c = $this->redis->get(static::PREFIX . static::ATTEMPT . $id)) !== false) return $c;
		return -1;
	}

	/**
	 * Cleanup the jobs data
	 *
	 * @param string 			$id
	 */
	public function cleanup(string $id)
	{
		$this->redis->delete([
			static::PREFIX . static::ATTEMPT . $id,
			static::PREFIX . static::MAX_RETRIES . $id,
			static::PREFIX . static::DATA . $id,
		]);
	}

	/**
	 * Will clear freaking everything
	 * !!! Attention with this one..
	 * 
	 * @return void
	 */
	public function clearEverything()
	{
		$this->redis->delete($this->redis->keys(static::PREFIX . '*'));
	}

	/**
	 * Simply store a value
	 *
	 * @param string 			$key
	 * @param mixed 			$value
	 * @return void
	 */
	public function storeStatsValue(string $key, $value)
	{
		$this->redis->set(static::PREFIX . static::STATS . $key, $value);
	}

	/**
	 * Simply get a value
	 *
	 * @param string 			$key
	 * @return void
	 */
	public function getStatsValue(string $key)
	{
		return $this->redis->get(static::PREFIX . static::STATS . $key);
	}
}