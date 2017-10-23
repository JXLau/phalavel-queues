<?php
namespace Phalavel\Queues;
use Carbon\Carbon;
use Closure;
use Phalcon\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use SuperClosure\Serializer;

/**
* Base Queue class with common methods for all types of queues
*/
abstract class Queue implements InjectionAwareInterface
{
    /**
     * Phalcon DI
     * @var Phalcon\DI\FactoryDefault
     */
    protected $_di;

    /**
     * Set Phalcon DI
     * @param DiInterface $di 
     */
    public function setDi( DiInterface $di )
    {
        $this->_di = $di;
    }

    /**
     * Get Phalcon DI
     * @return Phalcon\DI\FactoryDefault
     */
    public function getDi()
    {
        return $this->_di;
    }

    /**
     * Get queue type
     * @return string
     */
    abstract public function getType();

    /**
     * Push job to the queue
     * @param  \Closure|Phalavel\Queues\Job $job
     */
    abstract public function push($job);

    /**
     * Retreive jobs from queue
     * @return Array Set of Jobs
     */
    abstract public function pull($params);

    /**
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    protected function getTime()
    {
        return Carbon::now()->getTimestamp();
    }

    /**
     * Create payload from for storing somewhere
     * @param  \Closure|Phalavel\Queues\Job $job
     * @return string
     */
    protected function createPayload($job)
    {
        if ($job instanceof Job) {
            return serialize($job);
        }
        elseif ($job instanceof Closure) {
            $serializer = new Serializer();
            return $serializer->serialize($job);
        }
        return null;
    }

}