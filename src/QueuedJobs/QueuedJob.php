<?php
namespace Phalavel\Queues\QueuedJobs;

use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalavel\Queues\Job;

/**
 * Base class for retreived job
 */
abstract class QueuedJob extends Job implements InjectionAwareInterface
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
     * Unserialize job and execute it
     * @param  string $payload
     * @return void
     */
    public function resolveAndFire($payload)
    {
        $resolved = unserialize($payload);
        if ($resolved instanceof Job) {
            $resolved->setDi($this->getDi());
            $resolved->handle();
        }
    }

    public function handle()
    {
    }
}