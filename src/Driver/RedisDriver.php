<?php 

namespace Beryllium\Driver;

use Beryllium\Exception\InvalidDataException;
use Beryllium\Job;
use Redis;

class RedisDriver implements DriverInterface
{
    /**
     * Redis Keys
     */
    public const REDIS_KEY_WAITLIST = 'waitlist';
    public const REDIS_KEY_ATTEMPT = 'attempt.';
    public const REDIS_KEY_MAX_RETRIES = 'max_retries.';
    public const REDIS_KEY_DATA = 'data.';
    public const REDIS_KEY_STATS = 'stats.';

    /**
     * The redis key prefix
     */
    protected string $queuePrefix = 'beryllium.queue.';

    /**
     * The redis lock key prefix
     */
    protected string $lockPrefix = 'beryllium.lock.';

    /**
     * Constructor
     * 
     * @param Redis $redis The redis instance.
     */
    public function __construct(protected Redis $redis)
    {
    }

    /**
     * Queue Methods
     * 
     * ------------------------------------------------------------------------
     */

    /** 
     * Sets the queues key prefix
     *
     * @param string $prefix
     * 
     * @return void
     */
    public function setQueueKeyPrefix(string $prefix) : void
    {
        $this->queuePrefix = $prefix;
    }

    /** 
     * Sets the key prefix
     * 
     * @deprecated
     * @param string $prefix
     * 
     * @return void
     */
    public function setKeyPrefix(string $prefix) : void
    {
        trigger_error('Method ' . __METHOD__ . ' is deprecated, use setQueueKeyPrefix instead.', E_USER_DEPRECATED);
        $this->setQueueKeyPrefix($prefix);
    }

    /** 
     * Sets the lock key prefix
     *
     * @param string $prefix
     * 
     * @return void
     */
    public function setLockKeyPrefix(string $prefix) : void
    {
        $this->lockPrefix = $prefix;
    }

    /**
     * Adds the given Job to the queue
     *
     * @param Job $job
     * @param int $maxRetries
     * 
     * @return void
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
     * Get a job instance by the given id.
     *
     * @param string $id The Job identifier.
     * 
     * @return Job|null
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
     * Check if a job exists in the queue
     * 
     * @param string $id The Job identifier.
     * 
     * @return bool 
     */
    public function exists(string $id) : bool
    {
        $iterator = null;
        $keys = $this->redis->scan(
            $iterator, 
            $this->queuePrefix . static::REDIS_KEY_DATA . $id . '*',
            1
        );
        if ($keys !== false && count($keys) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the ID of a waiting job
     *
     * @return string|null Returns null if no job is queued.
     */
    public function popWaitingId() : ?string
    {
        return (string)$this->redis->rPop($this->queuePrefix . static::REDIS_KEY_WAITLIST) ?: null;
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
     * @param string $id The Job identifier.
     * 
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
     * @param string $id The Job identifier.
     * 
     * @return int Returns -1 if the job has never been executed
     */
    public function getMaxRetries(string $id) : int
    {
        if (($c = $this->redis->get($this->queuePrefix . static::REDIS_KEY_MAX_RETRIES . $id)) !== false) return (int) $c;
        return -1;
    }

    /**
     * Get the number of attempts this job already had.
     *
     * @param string $id The Job identifier.
     * 
     * @return int Returns -1 if the job has never been executed
     */
    public function attemptCount(string $id) : int
    {
        if (($c = $this->redis->get($this->queuePrefix . static::REDIS_KEY_ATTEMPT . $id)) !== false) return (int) $c;
        return -1;
    }

    /**
     * Cleanup the jobs data
     *
     * @param string $id The Job identifier.
     * 
     * @return void
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
     * Will clear freaking everything releated to the driver
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
     * @param string $key
     * @param mixed $value
     * 
     * @return void
     */
    public function storeStatsValue(string $key, $value) : void
    {
        $this->redis->set($this->queuePrefix . static::REDIS_KEY_STATS . $key, serialize($value));
    }

    /**
     * Simply get a value
     *
     * @param string $key
     * 
     * @return mixed
     */
    public function getStatsValue(string $key) : mixed
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
     * @param string $key
     * 
     * @return bool
     */
    public function isLocked(string $key) : bool
    {
        return (bool) $this->redis->exists($this->lockPrefix . $key);
    }

    /**
     * Returns the locks token
     *
     * @param string $key
     * 
     * @return string|null
     */
    public function getLockToken(string $key) : ?string
    {
        return $this->redis->get($this->lockPrefix . $key) ?: null;
    }

    /**
     * Creates a lock entry on the driver, this must be synchronised!
     *
     * @param string $key
     * @param string $token
     * @param int $ttl
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
     * @param string $key
     * @param string $token
     *
     * @return bool Retruns true if the lock could be removed.
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
