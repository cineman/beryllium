<?php 

namespace Beryllium\Driver;

use Redis;
use Beryllium\Job;

use Beryllium\Exception\InvalidDataException;

class RedisDriver implements DriverInterface
{	
	/** 
	 * Get the redis connection
	 */
	protected Redis $redis;

	/**
	 * The redis key prefix
	 */
	protected string $queuePrefix = 'beryllium.queue.';

	/**
	 * The redis lock key prefix
	 */
	protected string $lockPrefix = 'beryllium.lock.';

	/**
	 * Redis Keys
	 */
	const REDIS_KEY_WAITLIST = 'waitlist';
	const REDIS_KEY_ATTEMPT = 'attempt.';
	const REDIS_KEY_MAX_RETRIES = 'max_retries.';
	const REDIS_KEY_DATA = 'data.';
	const REDIS_KEY_STATS = 'stats.';

	/**
	 * Constructor
	 */
	public function __construct(Redis $redis)
	{
		$this->redis = $redis;
	}

	/**
	 * Queue Methods
	 * 
	 * ------------------------------------------------------------------------
	 */

	/** 
	 * Sets the queues key prefix
	 *
	 * @param string 			$prefix
	 * @return void
	 */
	public function setQueueKeyPrefix(string $prefix)
	{
		$this->queuePrefix = $prefix;
	}

	/** 
	 * @deprecated
	 * @param string 			$prefix
	 * @return void
	 */
	public function setKeyPrefix(string $prefix)
	{
		trigger_error('Method ' . __METHOD__ . ' is deprecated, use setQueueKeyPrefix instead.', E_USER_DEPRECATED);
		$this->setQueueKeyPrefix($prefix);
	}

	/** 
	 * Sets the lock key prefix
	 *
	 * @param string 			$prefix
	 * @return void
	 */
	public function setLockKeyPrefix(string $prefix)
	{
		$this->lockPrefix = $prefix;
	}

	/**
	 * Add a job to the queue
	 *
	 * @param Job 			$job
	 * @param int 			$maxRetries
	 */
	public function add(Job $job, int $maxRetries = 3) : void
	{
		$id = $job->id(); // get the job id

		$this->redis->lPush($this->queuePrefix . static::REDIS_KEY_WAITLIST, $id);

		$this->redis->set($this->queuePrefix . static::REDIS_KEY_ATTEMPT . $id, 0);
		$this->redis->set($this->queuePrefix . static::REDIS_KEY_MAX_RETRIES . $id, $maxRetries);
		$this->redis->set($this->queuePrefix . static::REDIS_KEY_DATA . $id, $job->serialize());

		// timeout the queue elements after an hour 
		// if there is an error somewhere this way we at least clear the garbage
		$this->redis->expire($this->queuePrefix . static::REDIS_KEY_ATTEMPT . $id, 3600);
		$this->redis->expire($this->queuePrefix . static::REDIS_KEY_MAX_RETRIES . $id, 3600);
		$this->redis->expire($this->queuePrefix . static::REDIS_KEY_DATA . $id, 3600);
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
		if (!$data = $this->redis->get($this->queuePrefix . static::REDIS_KEY_DATA . $id)) {
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
		return $this->redis->rPop($this->queuePrefix . static::REDIS_KEY_WAITLIST) ?: null;
	}

	/**
	 * Counts the number of jobs waiting for execution
	 *
	 * @return int
	 */
	public function waitingCount() : int
	{
		return (int) $this->redis->lLen($this->queuePrefix . static::REDIS_KEY_WAITLIST);
	}

	/**
	 * Reinsert the job into the waitlist
	 *
	 * @param string 			$id
	 * @return void
	 */
	public function retry(string $id) : void
	{
		$this->redis->incr($this->queuePrefix . static::REDIS_KEY_ATTEMPT . $id);
		$this->redis->lPush($this->queuePrefix . static::REDIS_KEY_WAITLIST, $id);
	}

	/**
	 * Get the maximum number of attempts we should try for the job
	 *
	 * @param string 				$id
	 * @return int
	 */
	public function getMaxRetries(string $id) : int
	{
		if (($c = $this->redis->get($this->queuePrefix . static::REDIS_KEY_MAX_RETRIES . $id)) !== false) return (int) $c;
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
		if (($c = $this->redis->get($this->queuePrefix . static::REDIS_KEY_ATTEMPT . $id)) !== false) return (int) $c;
		return -1;
	}

	/**
	 * Cleanup the jobs data
	 *
	 * @param string 			$id
	 */
	public function cleanup(string $id) : void
	{
		$this->redis->del([
			$this->queuePrefix . static::REDIS_KEY_ATTEMPT . $id,
			$this->queuePrefix . static::REDIS_KEY_MAX_RETRIES . $id,
			$this->queuePrefix . static::REDIS_KEY_DATA . $id,
		]);
	}

	/**
	 * Will clear freaking everything
	 * !!! Attention with this one..
	 * 
	 * @return void
	 */
	public function clearEverything() : void
	{
		$this->redis->del($this->redis->keys($this->queuePrefix . '*'));
		$this->redis->del($this->redis->keys($this->lockPrefix . '*'));
	}

	/**
	 * Stats Methods
	 * 
	 * ------------------------------------------------------------------------
	 */

	/**
	 * Simply store a value
	 *
	 * @param string 			$key
	 * @param mixed 			$value
	 * @return void
	 */
	public function storeStatsValue(string $key, $value) : void
	{
		$this->redis->set($this->queuePrefix . static::REDIS_KEY_STATS . $key, serialize($value));
	}

	/**
	 * Simply get a value
	 *
	 * @param string 			$key
	 * @return mixed
	 */
	public function getStatsValue(string $key)
	{
		if (($raw = $this->redis->get($this->queuePrefix . static::REDIS_KEY_STATS . $key)) === false) {
			throw new InvalidDataException("Could not read stats value from redis.");
		}

		return unserialize($raw);
	}

	/**
	 * Locking System Methods
	 * 
	 * ------------------------------------------------------------------------
	 */

	/**
	 * Checks if the given key is locked on the driver.
	 *
	 * @param string 					$key
	 * @return bool
	 */
	public function isLocked(string $key) : bool
	{
		return (bool) $this->redis->exists($this->lockPrefix . $key);
	}

	/**
	 * Returns the locks token
	 *
	 * @param string 					$key
	 * @return string
	 */
	public function getLockToken(string $key) : ?string
	{
		return $this->redis->get($this->lockPrefix . $key) ?: null;
	}

	/**
	 * Creates a lock entry on the driver, this must be synchronised!
	 *
	 * @param string 				$key
	 * @param string 				$token
	 * @param int 					$ttl
	 *
	 * @return bool Returns true if the lock could be created
	 */
	public function lock(string $key, string $token, int $ttl) : bool
	{
		return $this->redis->set($this->lockPrefix . $key, $token, ['NX', 'EX' => $ttl]);
	}


	/**
	 * Removes a lock entry on the driver, this must be synchronised!
	 * Also the lock for the key should only be removed if the token matches!
	 *
	 * @param string 				$key
	 * @param string 				$token
	 *
	 * @return bool
	 */
	public function unlock(string $key, string $token) : bool
	{
		$script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';

        return (bool) $this->redis->eval($script, [$this->lockPrefix . $key, $token], 1);
	}
}
