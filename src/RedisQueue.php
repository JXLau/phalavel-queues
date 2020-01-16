<?php
namespace Phalavel\Queues;
use Phalavel\Queues\LuaScripts;
use Phalavel\Queues\QueuedJobs\RedisJob;
use Illuminate\Support\Str;

/**
* Simple stub for queue, every job pushed
* in this queue will be executed immediatly
*/
class RedisQueue extends Queue
{
    protected static $redis;

    /**
     * Get queue type
     * @return string
     */
    public function getType()
    {
        return QueueManager::REDIS_QUEUE;
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return 'queues:'.($queue ?: 'default');
    }

    /**
     * Get redis
     * @return   [type]                   [description]
     */
    protected function getRedis()
    {
        if ($this->getDI()->has('queue_redis')) {
            return $this->getDI()->getShared('queue_redis');
        }
        
        return $this->getDI()->getShared('redis');
    }

    /**
     * @param  Phalavel\Queues\Job  
     * @param  string  $queue 
     * @param  integer $delay
     * @return void
     */
    public function push($job)
    {
        $queue = $job->queue;
        $delay = $job->delay ?: 0;

        $payload = $this->createPayload($job);
        if ($delay > 0) {
            return $this->getRedis()->zadd($this->getQueue($queue).':delayed', $this->getTime() + $delay, $payload);
        }
        return $this->getRedis()->rpush($this->getQueue($queue), $payload);
    }

    protected function createPayload($job)
    {
        $job->setAttemps(0);
        $job->id = $this->getRandomId();
        return parent::createPayload($job);
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId()
    {
        return Str::random(32);
    }

    /**
     * Since we process all jobs synchronusly,
     * no needs to retreive jobs from somewhere
     */
    public function pull($queue)
    {
        $queue = $this->getQueue($queue);
        $job = $this->pop($queue);

        return $job;
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function migrateExpiredJobs($from, $to)
    {
        $this->getRedis()->eval(
            LuaScripts::migrateExpiredJobs(), 2, $from, $to, $this->getTime()
        );
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param  string  $queue
     * @param  string  $job
     * @return void
     */
    public function deleteReserved($queue, $job)
    {
        $this->getRedis()->zrem($queue.':reserved', $job);
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  int  $delay
     * @return void
     */
    public function deleteAndRelease($queue, $reserved, $job, $delay)
    {
        $this->getRedis()->eval(
            LuaScripts::release(), 2, $queue.':delayed', $queue.':reserved', 
            $reserved, $job, $this->getTime() + $delay
        );
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param  string  $queue
     * @param  string  $job
     * @param  int  $delay
     * @return void
     */
    public function failedAndRelease($queue, $reserved, $job)
    {
        $this->getRedis()->eval(
            LuaScripts::release(), 2, $queue.':failed', $queue.':reserved', $reserved, $job, $this->getTime()
        );
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = 'default')
    {
        $this->migrateExpiredJobs($queue.':delayed', $queue);

        $expire = 3;

        if (! is_null($expire)) {
            $this->migrateExpiredJobs($queue.':reserved', $queue);
        }

        list($job, $reserved) = $this->getRedis()->eval(
            LuaScripts::pop(), 2, $queue, $queue.':reserved', $this->getTime() + $expire
        );

        if ($reserved) {
            $redis_job = new RedisJob($this, $job, $reserved, $queue);
            $redis_job->setDi($this->getDi());
            return $redis_job;
        }
    }
}