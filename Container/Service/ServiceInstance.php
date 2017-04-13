<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Ioc\Exception\exContainerNoService;
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
        // TODO fix
        /*$instance = new ServiceInstance($name, array(
            'service' => $instance,
            'options' => $options,
        ));*/

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
            if (class_exists($service))
            {
                $rClass        = new \ReflectionClass($service);

                if ($rClass->hasMethod('__construct'))
                {
                    // Resolve Arguments to constructor and create new instance
                    $argsAsService = $this->_resolveServicesAsArgument($rClass);

                    $argsAvailable = array_merge($argsAsService, $argsAvailable);

                    $rMethod  = $rClass->getMethod('__construct');
                    $resolved = \Poirot\Std\Invokable\resolveArgsForReflection($rMethod, $argsAvailable);
                    $service  = $rClass->newInstanceArgs($resolved);

                }
                else
                {
                    // service without constructor
                    $service = new $service;

                }

                // let remind options used as features like configurable
                if (isset($resolved)) {
                    foreach ($resolved as $key => $value) {
                        // Remove Resolved From Available Arguments
                        $key = (string) \Poirot\Std\cast($key)->under_score();
                        if (!isset($argsAvailable[$key])) continue;

                        unset($argsAvailable[$key]);
                    }
                }

            }
            elseif ($this->services()->has($service)) {
                $service = $this->services()->fresh($service, $argsAvailable);
                $argsAvailable = null;
            } else {
                throw new exContainerNoService(sprintf(
                    'Service with name (%s) not found.'
                    , $service
                ));
            }
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
    function setService($class)
    {
        $this->service = $class;
    }


    // ..

    protected function _resolveServicesAsArgument(\ReflectionClass $rClass)
    {
        $rMethod  = $rClass->getMethod('__construct');
        $mapServices = $this->__makeMapOfArgumentToService($rMethod->getDocComment());

        ## look for arguments as registered service ioc name
        $argsAsService = array();
        foreach ($rMethod->getParameters() as $reflectionParameter)
        {
            // look for argument as a service in current ioc domain(iCServiceAware injected)
            $service = $reflectionParameter->getName();

            if ( isset($mapServices[$service]) ) {
                // look for service from DocComment Block Definition
                $service_path = $mapServices[$service]['service'];
                if ($service_path) {
                    if (substr($service_path, -1) == '/')
                        // /module/oauth2/services/[x]<- !! resolve argument name here
                        // append argument as service name with given path
                        $service = $service_path.$service;
                    else
                        // /module/oauth2/services/grantResponder
                        // whole path to resolve service for argument name is given
                        $service = $service_path;
                } else
                    // service in current ioc domain, don't prefixed argument
                    VOID;
            }


            if ($this->services()->has($service))
                $argsAsService[$service] = $this->services()->get($service);
        }


        return $argsAsService;
    }

    private function __makeMapOfArgumentToService($getDocComment)
    {
        $mapServices = array();
        if (empty($getDocComment))
            // Nothing To Do !!!
            return $mapServices;

        /**
         * constructor.
         *
         * @param GrantAggregateGrants      $grantResponder       @IoC /module/oauth2/services/grantResponder
         * @param GrantAggregateGrants      $grantResponder       @IoC /module/oauth2/services/[x]<- !! resolve argument name here
         * @param iRepoUsersApprovedClients $RepoApprovedClients  @IoC /module/oauth2/services/repository/Users.ApprovedClients
         * @param $RepoApprovedClients                            @IoC /module/oauth2/services/repository/Users.ApprovedClients
         * @param iRepoUsersApprovedClients $UsersApprovedClients !! when ioc not present try to retrieve from current services() instance
         *                                                        !! resolve argument name here
         * @param $RepoApprovedClients                            @IoC from/current/domain/path !! resolve relative from current domain ioc
         */
        $regex = '/(@param\s*)(?P<type_hint>[\w\|]+\s*|)(?P<name>[$\w\|]+\s*)(@IoC\s*|)(?P<service_path>[\w\\/._-]+\s*|)/';
        if (preg_match_all($regex, $getDocComment, $matches)) {
            foreach ($matches['name'] as $i => $argument) {
                $argumentName = ltrim(trim($argument), '$');
                $servicePath  = $matches['service_path'][$i];
                $typeHint     = $matches['type_hint'][$i];

                $mapServices[$argumentName]['service'] = trim($servicePath);
                $mapServices[$argumentName]['type']    = trim($typeHint);
            }
        }

        return $mapServices;
    }
}

