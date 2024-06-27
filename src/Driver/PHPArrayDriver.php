<?php 

namespace Beryllium\Driver;

use Beryllium\Job;

class PHPArrayDriver implements DriverInterface
{
    /**
     * Array queued jobs
     * 
     * @var array<string, Job>
     */
    private array $jobs = [];

    /**
     * @var array<string>
     */
    private array $waitlist = [];

    /**
     * @var array<string, int>
     */
    private array $maxRetires = [];

    /**
     * @var array<string, int>
     */
    private array $retryCount = [];

    /**
     * @var array<string, mixed>
     */
    private array $stats = [];

    /**
     * @var array<string, array{string, int}>
     */
    private array $locks = [];

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
        $this->jobs[$job->id()] = $job;
        $this->maxRetires[$job->id()] = $maxRetries;
        $this->retryCount[$job->id()] = 0;
        $this->waitlist[] = $job->id();
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
        return $this->jobs[$id] ?? null;
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
        return array_key_exists($id, $this->jobs);
    }

    /**
     * Get the ID of a waiting job
     *
     * @return string|null Returns null if no job is queued.
     */
    public function popWaitingId() : ?string
    {
        return array_shift($this->waitlist);
    }

    /**
     * Counts the number of jobs waiting for execution
     *
     * @return int
     */
    public function waitingCount() : int
    {
        return count($this->waitlist);
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
        $this->retryCount[$id]++;
        $this->waitlist[] = $id;
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
        return $this->maxRetires[$id] ?? -1;
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
        return $this->retryCount[$id] ?? -1;
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
        unset(
            $this->jobs[$id],
            $this->maxRetires[$id],
            $this->retryCount[$id]
        );
    }

    /**
     * Will clear freaking everything releated to the driver
     * !!! Attention with this one..
     * 
     * @return void
     */
    public function clearEverything() : void
    {
        $this->jobs = [];
        $this->waitlist = [];
        $this->maxRetires = [];
        $this->retryCount = [];
    }

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
        $this->stats[$key] = $value;
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
        return $this->stats[$key] ?? null;
    }

    /**
     * Checks if the given key is locked on the driver.
     *
     * @param string $key
     * 
     * @return bool
     */
    public function isLocked(string $key) : bool
    {
        return isset($this->locks[$key]);
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
        return $this->locks[$key][0] ?? null;
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
        if (isset($this->locks[$key])) {
            return false;
        }

        $this->locks[$key] = [$token, $ttl];
        return true;
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
        if (!isset($this->locks[$key])) {
            return false;
        }

        if ($this->locks[$key][0] !== $token) {
            return false;
        }

        unset($this->locks[$key]);
        return true;
    }
}
