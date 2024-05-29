<?php 

namespace Beryllium;

use Beryllium\Exception\InvalidJobException;

class Job
{
    /**
     * Unserialize the given data to a job
     * 
     * @param string                $data
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
     * @var string
     */
    private string $id;

    /**
     * The jobs action
     *
     * @var string
     */
    private string $action;

    /**
     * The jobs parameters
     *
     * @var array<mixed>
     */
    private array $parameters;

    /**
     * Construct
     *
     * @param string            $id
     * @param string            $action
     * @param array<mixed>      $parameters
     */
    public function __construct(string $id, string $action, array $parameters = [])
    {
        $this->id = $id;
        $this->action = $action;
        $this->parameters = $parameters;
    }

    /**
     * Get the jobs id
     *
     * @return string 
     */
    public function id() : string
    {
        return $this->id;
    }

    /**
     * Get the jobs action
     *
     * @return string
     */
    public function action() : string
    {
        return $this->action;
    }

    /**
     * Get the jobs parameters
     *
     * @return array<mixed>
     */
    public function parameters() : array
    {
        return $this->parameters;
    }

    /**
     * Get a specific parameter from the job
     *
     * @param string                $key
     * @param mixed                 $default 
     * @return mixed
     */
    public function parameter(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    /** 
     * Serialize the Job
     *
     * @throws InvalidJobException
     *
     * @return string
     */
    public function serialize() : string
    {
        if (($serialized = json_encode(['id' => $this->id, 'action' => $this->action, 'data' => $this->parameters])) === false) {
            throw new InvalidJobException("Could not serialize Beryllium Job with ID '{$this->id}'. " . json_last_error_msg());
        }

        return $serialized;
    }
}
