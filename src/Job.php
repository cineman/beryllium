<?php 

namespace Beryllium;

class Job
{
	/**
	 * Unserialize the given data to a job
	 * 
	 * @param string 				$data
	 * @return Job
	 */
	public static function unserialize(string $data) : ?Job
	{
		$data = json_decode($data, true); 

		if (
			(!isset($data['id'])) || 
			(!isset($data['action'])) || 
			(!isset($data['data']))
		) {
			return null;
		}

		return new Job($data['id'], $data['action'], $data['data']);
	}

	/**
	 * The jobs id
	 *
	 * @param string
	 */
	private $id;

	/**
	 * The jobs action
	 *
	 * @param string
	 */
	private $action;

	/**
	 * The jobs parameters
	 *
	 * @param string
	 */
	private $parameters;

	/**
	 * Construct
	 *
	 * @param DriverInterface 			$driver
	 */
	public function __construct(string $id, string $action, array $parameters = [])
	{
		$this->id = $id;
		$this->action = $action;
		$this->parameters = $parameters;
	}

	/**
	 * Get the jobs id
	 */
	public function id() : string
	{
		return $this->id;
	}

	/**
	 * Get the jobs action
	 */
	public function action() : string
	{
		return $this->action;
	}

	/**
	 * Get the jobs parameters
	 */
	public function parameters() : array
	{
		return $this->parameters;
	}

	/**
	 * Get a specific parameter from the job
	 */
	public function parameter($key, $default = null)
	{
		return $this->parameters[$key] ?? $default;
	}

	/** 
	 * Serialize the Job
	 *
	 * @return string
	 */
	public function serialize() : string
	{
		return json_encode(['id' => $this->id, 'action' => $this->action, 'data' => $this->parameters]);
	}
}