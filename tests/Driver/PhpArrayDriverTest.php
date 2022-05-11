<?php 

namespace Beryllium\Tests\Driver;

use Beryllium\Driver\DriverInterface;
use Beryllium\Driver\PHPArrayDriver;
use Beryllium\Job;

class PhpArrayDriverTest extends BaseDriverTest
{
    /**
	 * This method should return an instance of your driver.
     * The instance should optimally use a real backend as long as
     * no additional dependencies are requried.
     * 
     * All drivers are required to return the same result to pass the test case.
	 */
	protected function createDriverInstance() : DriverInterface
    {
        return new PHPArrayDriver;
    }

    /**
     * Returns the string class name of your driver
     */
    protected function getDriverClassName() : string
    {
        return PHPArrayDriver::class;
    }
}
