<?php 

namespace Beryllium;

use Symfony\Component\Process\Process;

class ProcessManager
{	
	/**
	 * A queue instance
	 *
	 * @var Queue
	 */
	protected $queue;

	/**
	 * An array of workers
	 *
	 * @var array
	 */
	protected $workers = [];

	/**
	 * Maximum number of workers
	 *
	 * @var int
	 */
	protected $maxWorkers = 32;

	/**
	 * Should we exit the main loop
	 *
	 * @var bool
	 */
	protected $shouldExit = false;

	/**
	 * Idle wait in microseconds
	 *
	 * @var int
	 */
	protected $idleWait = 10000;

	/**
	 * The process pattern
	 *
	 * @var string  
	 */ 
	protected $processPattern;	

	/**
	 * Construct
	 *
	 * @param Queue 			$queue
	 */
	public function __construct(Queue $queue, $processPattern)
	{
		$this->queue = $queue;
		$this->processPattern = $processPattern;
	}

	/**
	 * Start the main loop
	 * ! This method is blocking !
	 *
	 * @return void
	 */
	public function work(bool $verbose = false, bool $printWorkerOutput = false)
	{
		while(!$this->shouldExit)
		{
			usleep($this->idleWait);

			// check the worker status
			foreach($this->workers as $jobId => $process) 
			{
				if (!$process->isRunning()) 
				{
					// if the process failed we might retry
					if (!$process->isSuccessful()) {
						if ($verbose) echo "[{$jobId}] failed\n";
						$this->queue->considerRetry($jobId);
					} else {
						if ($verbose) echo "[{$jobId}] success\n";
						$this->queue->done($jobId);
					}

					if ($printWorkerOutput) echo "[{$jobId}] {$process->getOutput()}\n";
					
					unset($this->workers[$jobId]);
				}
			}

			if (count($this->workers) >= $this->maxWorkers) {
				continue;
			}

			// get the next job
			if (!$jobId = $this->queue->getNextJobId()) {
				continue;
			}

			$process = new Process(sprintf($this->processPattern, $jobId));
			$process->start();

			$this->workers[$jobId] = $process;

			if ($verbose) echo "[{$jobId}] starting\n";

			// update the number of active jobs
			$this->queue->statsSetActiveWorkers(count($this->workers));
		}
	}
}
