<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Std\Interfaces\Pact\ipOptionsProvider;

class InstanceService
    extends aContainerService
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
     * @param array|callable $nameOrSetter
     * @param null|string    $setter
     */
    function __construct($nameOrSetter = null, $setter = null)
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
    function createService()
    {
        $service = $this->service;

        if(is_string($this->service) && class_exists($this->service))
            // TODO options as new instance constructor; use resolver
            $service = new $service($this->optsData());

        if ($service instanceof ipOptionsProvider)
            ## using Pact Options Provider Contract
            $service->optsData()->import($this->optsData());

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
