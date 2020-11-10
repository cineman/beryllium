<?php 

namespace Beryllium\Tests;

use Symfony\Component\Process\Process;

// load the test env
list(
    $queue,
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
    }
}
