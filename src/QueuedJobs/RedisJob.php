<?php
namespace Phalavel\Queues\QueuedJobs;

use Phalavel\Queues\QueuedJobInterface;
use Phalavel\Queues\RedisQueue;

/**
* Class for RedisQueue jobs
*/
class RedisJob extends QueuedJob implements QueuedJobInterface
{
    /**
     * Job
     * @var stdClass
     */
    protected $job;

    /**
     * The Redis queue instance.
     *
     * @var \Phalavel\Queues\RedisQueue
     */
    protected $redis;

    /**
     * The Redis job payload inside the reserved queue.
     *
     * @var string
     */
    protected $reserved;

    /**
     * queue name
     * @var [type]
     */
    protected $queue;

    /**
     * Create new instance of DatabaseJob
     * @param StdClass $job
     */
    public function __construct(RedisQueue $redis, $job, $reserved, $queue)
    {
        $this->redis = $redis;
        $this->job = $job;
        $this->reserved = $reserved;
        $this->queue = $queue;
    }
    
    /**
     * Execute job
     * @return void
     */
    public function fire()
    {
        $this->resolveAndFire($this->job);
    }

    /**
     * Get job id
     * @return mixed
     */
    public function getId()
    {
        return $this->job->id;
    }

    /**
     * Postpone failed job
     * @param  int
     * @return void
     */
    public function tryAgain($timeout)
    {
        $reserved = unserialize($this->reserved);
        $reserved->setAttemps($this->attemps()+1);

        $job = serialize($reserved);

        $this->redis->deleteAndRelease($this->queue, $this->reserved, $job, $timeout);
    }

    /**
     * Get attemps count of the job
     * @return int
     */
    public function attemps()
    {
        $job = unserialize($this->job);
        return (int) $job->attemps;
    }

    /**
     * Mark job as done
     * @return void
     */
    public function markAsDone()
    {
        $this->redis->deleteReserved($this->queue, $this->reserved);
    }

    /**
     * Mark job as failed
     * @return void
     */
    public function markAsFailed()
    {
        $this->redis->deleteReserved($this->queue, $this->reserved);
    }

    /**
     * Get redis
     * @return   [type]                   [description]
     */
    protected function getRedis()
    {
        return $this->getDI()->getShared('redis');
    }
}