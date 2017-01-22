<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Std\Interfaces\Pact\ipConfigurable;
use Poirot\Std\Interfaces\Struct\iData;


class ServiceInstance
    extends aServiceContainer
{
    const KEY_OPTIONS = 'options';
    
    protected $service;


    /**
     * Construct
     *
     * also can used as:
     *   - new ServiceInstance('name', $service);
     *   - new ServiceInstance('name', [ 'service' => [..] ..options]);
     *     or setter set
     *   - new ServiceInstance([ 'service' => [..] ..options])
     *
     *   $service can be any type
     *   string consider class name or either registered service
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
     * @return object
     * @throws \Exception
     */
    function newService()
    {
        $service = $this->service;

        $argsAvailable = \Poirot\Std\cast($this->optsData())->toArray();

        if (is_string($service)) {
            if (class_exists($service)) {
                $argsAvailable = \Poirot\Std\cast($this->optsData())->toArray();
                $rClass        = new \ReflectionClass($service);

                if ($rClass->hasMethod('__construct')) {
                    // Resolve Arguments to constructor and create new instance
                    $rMethod  = $rClass->getMethod('__construct');

                    ## look for arguments as registered service ioc name
                    $argsAsService = array();
                    foreach ($rMethod->getParameters() as $reflectionParameter) {
                        $paramName = $reflectionParameter->getName();
                        if ($this->services()->has($paramName))
                            $argsAsService[$reflectionParameter->getName()] = $this->services()->get($paramName);
                    }

                    $argsAvailable = array_merge($argsAsService, $argsAvailable);

                    $resolved = \Poirot\Std\Invokable\resolveArgsForReflection($rMethod, $argsAvailable);
                    $service  = $rClass->newInstanceArgs($resolved);
                } else {
                    // service without constructor
                    $service = new $service;
                }

                // let remind options used as features like configurable
                // TODO array_diff not work with multidimensional array; implement in stdArray
                // $argsAvailable = array_diff($argsAvailable, $resolved);

                if ($argsAvailable) {
                    if ($service instanceof ipConfigurable)
                        ## using Pact Options Provider Contract
                        $service->with($argsAvailable);
                    elseif ($service instanceof iData)
                        $service->import($argsAvailable);
                }

            } elseif ($this->services()->has($service))
                $service = $this->services()->fresh($service, $argsAvailable);
        }

        return $service;
    }

    /**
     * @param mixed $class
     */
    function setService($class)
    {
        $this->service = $class;
    }
}

