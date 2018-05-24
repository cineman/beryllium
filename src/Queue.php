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
	 * Create a new job and enqueue it.
	 *
	 * @param string 			$action
	 * @param array 			$parameters
	 *
	 * @return string Returns the job id 
	 */
	public function add(string $action, array $parameters = [])
	{
		$this->driver->add(new Job(uniqid('', true), $action, $parameters));
	}
}