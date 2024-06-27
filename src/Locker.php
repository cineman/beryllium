<?php 

namespace Beryllium;

use Beryllium\Driver\DriverInterface;

class Locker
{
    /**
     * Constructor
     *
     * @param DriverInterface $driver The beryllium driver.
     * 
     * @return void 
     */
    public function __construct(private DriverInterface $driver)
    {
    }

    /**
     * Creates a mutex with the given key and the locker assigned driver
     *
     * @param string $lockkey
     * 
     * @return Mutex
     */
    public function mutex(string $lockkey) : Mutex
    {
        return new Mutex($this->driver, $lockkey);
    }
}
