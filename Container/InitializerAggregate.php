<?php
namespace Poirot\Ioc\Container;

use SplPriorityQueue;
use Poirot\Ioc\Container\Interfaces\iContainerInitializer;

class InitializerAggregate
    implements iContainerInitializer
{
    /** @var SplPriorityQueue */
    protected $priorityQueue;

    /**
     * Add Callable Method
     *
     * Methods will bind to service immediately:
     * [code]
     * function($instance) {
     *  if ($instance instanceof Application\ModuleAsService)
     *      // do something
     * }
     * [/code]
     *
     * !! and get service as first argument if service not object
     * function($service)
     *
     * @param callable $methodCallable  Callable
     * @param int      $priority        Priority
     *
     * @return $this
     */
    function addCallable($methodCallable, $priority = 10)
    {
        if (!is_callable($methodCallable))
            throw new \InvalidArgumentException('Param must be callable');

        $this->_getPriorityQueue()->insert($methodCallable, $priority);
        return $this;
    }

    /**
     * Add Initializer Interface
     * Add Initializer Interface
     *
     * @param iContainerInitializer $initializer Initializer Interface
     * @param null|int     $priority    Priority
     *
     * @return $this
     */
    function addInitializer(iContainerInitializer $initializer, $priority = null)
    {
        $priority = ($priority == null)
            ? ( ($initializer->getPriority() !== null) ? $initializer->getPriority() : 10)
            : $priority;

        $this->_getPriorityQueue()->insert($initializer, $priority);
        return $this;
    }

    /**
     * Initialize Service
     *
     * @param mixed $instance Service
     *
     * @return mixed
     */
    function initialize($instance)
    {
        foreach(clone $this->_getPriorityQueue() as $initializer)
        {
            if (!is_callable($initializer) && !$initializer instanceof iContainerInitializer)
                throw new \InvalidArgumentException(sprintf(
                    'Invalid Initializer Provided. (%s).'
                    , \Poirot\Std\flatten($initializer)
                ));

            if ($initializer instanceof iContainerInitializer)
                $initializer->initialize($instance);
            else /* Callable */
                $this->_initializeCallable($initializer, $instance);
        }
    }

    protected function _initializeCallable($initializer, $instance)
    {
        if ($initializer instanceof \Closure)
            // DO_LEAST_PHPVER_SUPPORT 5.4 closure bindto
            if (is_object($instance) && version_compare(phpversion(), '5.4.0') >= 0)
                // Bind Initializer within service
                $initializer = $initializer->bindTo($instance);

        call_user_func($initializer, $instance);
    }

    /**
     * Used To Bind Initializer
     *
     * @return int
     */
    function getPriority()
    {
        return 0;
    }


    // ...

    /**
     * internal priority queue
     *
     * @return SplPriorityQueue
     */
    protected function _getPriorityQueue()
    {
        if (!$this->priorityQueue)
            $this->priorityQueue = new SplPriorityQueue();

        return $this->priorityQueue;
    }
}
