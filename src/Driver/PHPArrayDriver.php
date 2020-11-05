<?php 

namespace Beryllium\Driver;

use Beryllium\Job;

class PHPArrayDriver implements DriverInterface
{
    /**
     * Adds the given Job to the queue
     *
     * @param Job           $job
     * @param int           $maxRetries
     * @return void
     */
    public function add(Job $job, int $maxRetries = 3)
    {

    }

    /**
     * Get a job instance by the given id.
     *
     * @param string            $id The Job identifier.
     * @return Job
     */
    public function get(string $id) : ?Job
    {

    }

    /**
     * Get the ID of a waiting job
     *
     * @return string|null Returns null if no job is queued.
     */
    public function popWaitingId() : ?string
    {

    }

    /**
     * Counts the number of jobs waiting for execution
     *
     * @return int
     */
    public function waitingCount() : int
    {

    }

    /**
     * Reinsert the job into the waitlist
     *
     * @param string            $id The Job identifier.
     * @return void
     */
    public function retry(string $id)
    {

    }

    /**
     * Get the maximum number of attempts we should try for the job
     *
     * @param string                $id The Job identifier.
     * @return int                      Returns -1 if the job has never been executed
     */
    public function getMaxRetries(string $id) : int
    {

    }

    /**
     * Get the number of attempts this job already had.
     *
     * @param string            $id The Job identifier.
     * @return int                  Returns -1 if the job has never been executed
     */
    public function attemptCount(string $id) : int
    {

    }

    /**
     * Cleanup the jobs data
     *
     * @param string            $id The Job identifier.
     * @return void
     */
    public function cleanup(string $id)
    {

    }

    /**
     * Will clear freaking everything releated to the driver
     * !!! Attention with this one..
     * 
     * @return void
     */
    public function clearEverything()
    {

    }

    /**
     * Simply store a value
     *
     * @param string            $key
     * @param mixed             $value
     * @return void
     */
    public function storeStatsValue(string $key, $value)
    {

    }

    /**
     * Simply get a value
     *
     * @param string            $key
     * @return mixed
     */
    public function getStatsValue(string $key)
    {

    }

    /**
     * Checks if the given key is locked on the driver.
     *
     * @param string                    $key
     * @return bool
     */
    public function isLocked(string $key) : bool
    {

    }

    /**
     * Returns the locks token
     *
     * @param string                    $key
     * @return string
     */
    public function getLockToken(string $key) : ?string
    {

    }

    /**
     * Creates a lock entry on the driver, this must be synchronised!
     *
     * @param string                $key
     * @param string                $token
     * @param int                   $ttl
     *
     * @return bool Returns true if the lock could be created
     */
    public function lock(string $key, string $token, int $ttl) : bool
    {

    }

    /**
     * Removes a lock entry on the driver, this must be synchronised!
     * Also the lock for the key should only be removed if the token matches!
     *
     * @param string                $key
     * @param string                $token
     *
     * @return bool Retruns true if the lock could be removed.
     */
    public function unlock(string $key, string $token) : bool
    {

    }
}
