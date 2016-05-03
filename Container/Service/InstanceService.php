<?php
namespace Poirot\Ioc\Container\Service;

class InstanceService 
    extends aContainerService
{
    protected $service;

    /**
     * Construct
     *
     * also can used as:
     * - new InstanceService('name', $service);
     * or setter set
     * - new InstanceService([ 'service' => [..] ..options])
     *
     * @param array|callable $nameOrSetter
     * @param null|string    $setter
     */
    function __construct($nameOrSetter = null, $setter = null)
    {
        if (is_string($nameOrSetter)) {
            ## __construct('name', [$this, 'method'])
            $this->setName($nameOrSetter);
            $this->setService($setter);
        }
        
        parent::__construct($nameOrSetter);
    }

    /**
     * Create Service
     *
     * TODO $this->invoke_options as new instance constructor
     *
     * @return mixed
     */
    function createService()
    {
        if(is_string($this->service) && class_exists($this->service))
            $this->service = new $this->service;

        return $this->service;
    }

    /**
     * @param mixed $class
     */
    public function setService($class)
    {
        $this->service = $class;
    }
}
 