<?php 

namespace Beryllium\Tests;

use Symfony\Component\Process\Process;

// load the test env
list(
    $queue,
    $locker,
    $redis
) = require __DIR__ . DS . '..' . DS . 'test-bootstrap.php';

class IntegrationTest extends \PHPUnit\Framework\TestCase
{
    private function getTestQueueInstance()
    {
        global $queue; return $queue;
    }
    
    private function getTestRedisInstance()
    {
        global $redis; return $redis;
    }

    public function testParallelIncrement()
    {
        $queue = $this->getTestQueueInstance();
        $redis = $this->getTestRedisInstance();

        // start a process manager
        $pm = new Process(['php', realpath(__DIR__ . '/../bin/test-pm')]);
        $pm->start();

        $redis->del('beryllium.test.integration.incr');
        for($i=0; $i<10; $i++) {
            $queue->add('redis.incr', ['key' => 'beryllium.test.integration.incr']);
        }

        // wait for second
        sleep(1);

        // check the increment result
        $this->assertEquals(10, $redis->get('beryllium.test.integration.incr'));

        $pm->stop();
    }

    /**
     * This test similar to the one above will increase a counter 
     * with every job executed. 
     * Special here is that the job should fail 50% of the time, this allows 
     * us to test if the retry system is working correctly.
     */
    public function testParallelIncrementWithRandomFails()
    {
        $queue = $this->getTestQueueInstance();
        $redis = $this->getTestRedisInstance();

        // start a process manager
        $pm = new Process(['php', realpath(__DIR__ . '/../bin/test-pm')]);
        $pm->start();

        $redis->del('beryllium.test.integration.incr');
        for($i=0; $i<10; $i++) {
            // the propbability is quite low but the 
            // test could still fail with 100 retries
            // if that happens I pay who ever runs into the 
            // issue a beer.
            $queue->add('redis.incr.rfail', ['key' => 'beryllium.test.integration.incr'], 1000);
        }

        // wait for second
        sleep(1);

        // check the increment result
        $this->assertEquals(10, $redis->get('beryllium.test.integration.incr'));

        $pm->stop();
    }

    /**
     * This test runs a task reading a value from a file, increasing it and writing it back to the file
     * the operation is wrapped in a mutex safe exec, so the result should be the same as the number of jobs.
     */
    public function testParallelMutexLocking()
    {
        $queue = $this->getTestQueueInstance();

        // start a process manager
        $pm = new Process(['php', realpath(__DIR__ . '/../bin/test-pm')]);
        $pm->start();

        $incrfile = '/tmp/beryllium.test.incr';
        file_put_contents($incrfile, '0');

        for($i=0; $i<100; $i++) {
            $queue->add('file.incr_test', ['file' => $incrfile], 1000);
        }

        // wait for second
        sleep(3);

        // check the increment result
        $this->assertEquals(100, file_get_contents($incrfile));

        $pm->stop();
    }
}
