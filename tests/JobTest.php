<?php 

namespace Beryllium\Tests;

use Beryllium\Job;
use Beryllium\Exception\InvalidJobException;

class JobTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $this->assertInstanceOf(Job::class, new Job('foo', 'do_a', []));
    }

    public function testProperties()
    {
        $job = new Job('id42', 'test', ['foo' => 'bar', 'bar' => 'foo']);

        $this->assertEquals('id42', $job->id());
        $this->assertEquals('test', $job->action());
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo'], $job->parameters());
        $this->assertEquals('bar', $job->parameter('foo'));
        $this->assertEquals('foo', $job->parameter('bar'));
    }

    public function testSerialize()
    {
        $job = new Job('id42', 'test', ['foo' => 'bar', 'bar' => 'foo']);

        $raw = $job->serialize();

        $job2 = Job::unserialize($raw);

        $this->assertEquals($job, $job2);
        $this->assertNotSame($job, $job2);
    }

    public function testBadPayload()
    {
        $this->expectException(InvalidJobException::class);

        $job = new Job('id42', 'test', ['a' => NAN]);
        $job->serialize();
    }
}
