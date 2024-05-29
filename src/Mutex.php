<?php 

namespace Beryllium;

use Beryllium\Driver\DriverInterface;
use Beryllium\Exception\LockedMutexException;

class Mutex
{
    /**
     * Error codes
     */
    public const ERROR_ALREADY_LOCKED = 5;
    public const ERROR_UNLOCK_FAILURE = 10;

    /**
     * Driver instance
     *
     * @var DriverInterface
     */
    private DriverInterface $driver;

    /**
     * Mutex key
     *
     * @var string
     */
    private string $lockkey;

    /**
     * The mutex current token that ensures that only 
     * this instance can unlock a lock.
     *
     * @var string
     */
    private string $token;

    /**
     * Constructor
     *
     * @param DriverInterface $driver The beryllium driver.
     * @param string $lockkey
     * 
     * @return void 
     */
    public function __construct(DriverInterface $driver, string $lockkey)
    {
        $this->driver = $driver;
        $this->lockkey = $lockkey;
    }

    /**
     * Returns the currents mutexes lock key
     *
     * @return string 
     */
    public function getMutexKey() : string
    {
        return $this->lockkey;
    }

    /**
     * Lock the mutex
     * 
     * @param int $ttl Max time to live in seconds.
     * 
     * @return void
     * 
     * @throws LockedMutexException
     */
    public function lock(int $ttl = 30) : void
    {
        // generate a token
        $this->token = uniqid();

        // try to lock on the driver
        if (!$this->driver->lock($this->lockkey, $this->token, $ttl)) {
            throw new LockedMutexException("The mutex ($this->lockkey) is already locked.", static::ERROR_ALREADY_LOCKED);
        }
    }

    /**
     * Is the mutex already locked?
     *
     * @return bool
     */
    public function isLocked() : bool
    {
        return $this->driver->isLocked($this->lockkey);
    }

    /**
     * Is the mutex locked by the current mutex?
     *
     * @return bool
     */
    public function ownsLock() : bool
    {
        // if the mutex is not locked we assume false
        if (!$this->isLocked()) return false;

        // read the lock token and compare
        return $this->driver->getLockToken($this->lockkey) === $this->token;
    }

    /**
     * Lock the mutex
     * 
     * @return void 
     * 
     * @throws LockedMutexException 
     */
    public function unlock() : void
    {
        // try to lock on the driver
        if (!$this->driver->unlock($this->lockkey, $this->token)) {
            throw new LockedMutexException(
                "The mutex ($this->lockkey) could not be unlocked, either its been locked by another instance or it does not exist.", 
                static::ERROR_UNLOCK_FAILURE
            );
        }
    }

    /**
     * Runs the given callback in a mutex locked enclosure.
     * Catches any error that might occour and unlocks the mutex and rethrows the error / exception.
     *
     * @param callable $callback 
     * @param int $ttl
     * 
     * @return void
     * 
     * @throws \Error
     * @throws \Exception
     */
    public function safeExec(callable $callback, int $ttl = 10) : void
    {
        $this->lock($ttl);

        try {
            $callback($this->token);
        } catch (\Exception $e) {
            $this->unlock();
            throw $e;
        } catch (\Error $e) {
            $this->unlock();
            throw $e;
        }

        $this->unlock();
    }
}
