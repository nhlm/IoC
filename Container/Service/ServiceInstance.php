<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Std\Interfaces\Pact\ipConfigurable;

class ServiceInstance
    extends aServiceContainer
{
    protected $service;

    /**
     * Construct
     *
     * also can used as:
     * - new InstanceService('name', $service);
     * - new InstanceService('name', [ 'service' => [..] ..options]);
     * or setter set
     * - new InstanceService([ 'service' => [..] ..options])
     *
     * @param array|mixed $nameOrSetter
     * @param array       $setter
     */
    function __construct($nameOrSetter = null, $setter = array())
    {
        if (is_string($nameOrSetter) && !is_array($setter))
            ## new InstanceService('name', $service)
            $setter = array('service' => $setter);
        
        parent::__construct($nameOrSetter, $setter);
    }

    /**
     * Create Service
     *
     * @return mixed
     */
    function newService()
    {
        $service = $this->service;

        if(is_string($this->service) && class_exists($this->service))
            // TODO options as new instance constructor; use resolver
            $service = new $service($this->optsData());

        if ($service instanceof ipConfigurable)
            ## using Pact Options Provider Contract
            $service->with($this->optsData());

        return $service;
    }

    /**
     * @param mixed $class
     */
    public function setService($class)
    {
        $this->service = $class;
    }
}
