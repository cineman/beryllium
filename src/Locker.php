<?php 

namespace Beryllium;

use Beryllium\Driver\DriverInterface;

class Locker
{
    /**
     * Driver instance
     *
     * @var DriverInterface
     */
    private DriverInterface $driver;

    /**
     * Constructor
     *
     * @param DriverInterface               $driver The beryllium driver.
     * @return void 
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Creates a mutex with the given key and the locker assigned driver
     *
     * @param string                $lockkey
     * @return Mutex
     */
    public function mutex(string $lockkey) : Mutex
    {
        return new Mutex($this->driver, $lockkey);
    }
}
