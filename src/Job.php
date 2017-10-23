<?php
namespace Phalavel\Queues;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\DiInterface;

/**
* 
*/
abstract class Job implements InjectionAwareInterface
{

    protected $_di;

    protected $attemps;

    public function setDi( DiInterface $di )
    {
        $this->_di = $di;
    }

    public function getDi()
    {
        return $this->_di;
    }

    public function setAttemps($attemps)
    {
        $this->attemps = $attemps;
    }

    abstract public function handle();
    
}