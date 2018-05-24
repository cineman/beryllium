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

	public function getNextJob() 
	{
		return true;
	}
}