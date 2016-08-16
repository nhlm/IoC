<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Std\Interfaces\Pact\ipConfigurable;
use Poirot\Std\Interfaces\Struct\iData;

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

        $argsAvailable = \Poirot\Std\cast($this->optsData())->toArray();

        if(is_string($service) && class_exists($service)) {
            $argsAvailable = \Poirot\Std\cast($this->optsData())->toArray();
            $rClass   = new \ReflectionClass($service);
            $rMethod  = $rClass->getMethod('__construct');

            $resolved = \Poirot\Std\Invokable\resolveArgsForReflection($rMethod, $argsAvailable);
            $service  = $rClass->newInstanceArgs($resolved);

            // let remind options used as features like configurable
            $argsAvailable = @array_diff($argsAvailable, $resolved);
        }

        if ($argsAvailable) {
            if ($service instanceof ipConfigurable)
                ## using Pact Options Provider Contract
                $service->with($argsAvailable);
            elseif ($service instanceof iData)
                $service->import($argsAvailable);
        }

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

