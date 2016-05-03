<?php
namespace Poirot\Ioc;

use Poirot\Std\ErrorStack;

use Poirot\Ioc\Interfaces\iContainer;
use Poirot\Ioc\Interfaces\Respec\iServicesAware;
use Poirot\Ioc\Container\Interfaces\iContainerService;

use Poirot\Ioc\Container\BuilderContainer;
use Poirot\Ioc\Container\InitializerAggregate;
use Poirot\Ioc\Exception\exContainerCreateService;
use Poirot\Ioc\Exception\exContainerNoService;

class Container
    implements iContainer
{
    /** Separator between namespaces */
    const SEPARATOR = '/';

    /** @var string Container normalized namespace */
    protected $namespace = '';
    /** @var array Child Nested Containers */
    protected $__nestRight = array();
    /** @var null|Container Container That Nested To */
    protected $__nestLeft  = null;

    /** @var array Service Interfaces Contract */
    protected $implementations = array(
        # 'normalizedname' => '\To\iInterface',
        # 'normalizedname' => '\To\ClassImplementation',
    );

    /** @var array Registered Services */
    protected $services = array(
        # iContainerService,
    );

    /** @var array shared instances */
    protected $_c_createdServices = array();
    /** @var array Service Aliases */
    protected $aliases = array(/* 'normalizedname' => 'alias' */);
    /** @var array internal cache */
    protected $_c_normalizedNames = array();

    /** @var InitializerAggregate Instance Initializer */
    protected $initializer;

    /** @var array Create instance invoke options */
    protected $__invokeOptions;


    /**
     * Construct
     *
     * @param BuilderContainer $cBuilder
     *
     * @throws \Exception
     */
    function __construct(BuilderContainer $cBuilder = null)
    {
        if ($cBuilder !== null)
            $cBuilder->build($this);
    }

    /**
     * Set Container Namespace
     *
     * @param string $namespace
     *
     * @throws \Exception
     * @return $this
     */
    function setNamespace($namespace)
    {
        $this->namespace = $this->_normalizeServiceName($namespace);
        return $this;
    }

    /**
     * Get Container Normalized Namespace Name
     *
     * @return string
     */
    function getNamespace()
    {
        return $this->namespace;
    }


    // Service Manager:

    /**
     * Set Service Implementation Interface Contract
     *
     * $implement:
     *  - string '\InterfaceName'
     *  - object get implemented interfaces
     *
     * @param string        $serviceName Service name
     * @param string|object $implement   Interface Or Object Implementation
     *
     * @throws \Exception
     * @return $this
     */
    function setImplementation($serviceName, $implement)
    {
        if ($this->has($serviceName))
            throw new \Exception(
                "Service ({$serviceName}) is implemented; 
                Interface must define before service registration."
            );

        if (is_string($implement) && !(interface_exists($implement) || class_exists($implement)))
            throw new \InvalidArgumentException(
                "Invalid interface arguments, this must be valid interface name; given ({$implement})."
            );

        if (is_object($implement))
            $implement = get_class($implement);

        $serviceName = $this->_normalizeServiceName($serviceName);
        $this->implementations[$serviceName] = $implement;
        return $this;
    }

    /**
     * Get Implementation Interface of Service Contract
     *
     * @param string $serviceName
     *
     * @return string|false
     */
    function hasImplementation($serviceName)
    {
        $serviceName = $this->_normalizeServiceName($serviceName);

        return (
            isset($this->implementations[$serviceName])
                ? $this->implementations[$serviceName]
                : false
        );
    }

    /**
     * Register a service to container
     *
     * @param iContainerService $service Service
     *
     * @throws \Exception
     * @return $this
     */
    function set(iContainerService $service)
    {
        $name  = $service->getName();
        $cName = $this->_normalizeServiceName($name);
        if ($this->has($name) && !$this->services[$cName]->isAllowOverride())
            throw new \Exception(
                "A service by the name or alias ({$name}) already exists and cannot be overridden;"
            );

        $this->services[$cName] = $service;
        return $this;
    }

    /**
     * Check for a registered instance
     *
     * @param string $serviceName Service Name
     *
     * @return boolean
     */
    function has($serviceName)
    {
        $cName = $this->_normalizeServiceName($serviceName);
        return isset($this->services[$cName]);
    }

    /**
     * Retrieve a registered service
     * 
     * !! get always cache the last result of created service
     *    with same options; otherwise using ::fresh instead
     * 
     * !! create service of first retrieve and store it.
     *    if service not exists self::fresh will call.
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws \Exception
     * @return mixed
     */
    function get($serviceName, $invOpt = array())
    {
        $cName  = $this->_normalizeServiceName($serviceName);
        ## hash with options, so we get unique service with different options V
        $hashed = md5($cName.\Poirot\Std\flatten($invOpt));

        ## Service From Cache:
        if (!array_key_exists($hashed, $this->_c_createdServices)) {
            ## make new fresh instance if service not exists
            $instance = $this->fresh($serviceName, $invOpt);
            $this->_c_createdServices[$hashed] = $instance;

            ## recursion call to retrieve instance
            return $this->get($serviceName, $invOpt);
        }


        // ...

        # do return service instance
        $instance = $this->_c_createdServices[$hashed];
        return $instance;
    }

    /**
     * Retrieve a fresh instance of service
     *
     * @param string $serviceName Service name
     * @param mixed  $invOpt Invoke Options
     *
     * @throws \Exception
     * @return mixed
     */
    function fresh($serviceName, $invOpt = array())
    {
        $orgName     = $serviceName;
        $serviceName = $this->getExtended($serviceName);

        # check if we have alias to nested service ...................................................\
        if (strpos($serviceName, self::SEPARATOR) !== false) {
            // shared alias for nested container
            /* @see Container::extend */

            $xService    = explode(self::SEPARATOR, $serviceName);
            $serviceName = array_pop($xService);

            return $this->from(implode(self::SEPARATOR, $xService))->get($serviceName);
        }


        // ..

        if (!$this->has($serviceName))
            throw new exContainerNoService(sprintf(
                '%s (%s) was requested but no service could be found.'
                , ($serviceName !== $orgName)
                    ? "Service ($serviceName) with alias"
                    : 'Service'
                , $orgName
            ));


        # attain service instance ...................................................................\
        $cName = $this->_normalizeServiceName($serviceName);
        /** @var iContainerService $inService */
        $inService = $this->services[$cName];

        # Refresh Service:
        try
        {
            ## invokeOptions used to build service
            $inService = clone $inService;
            $inService->optsData()->import($invOpt);
            $instance = $this->_createFromService($inService);
            if ($instance === null)
                throw new \Exception('service meanwhile found create nothing(null).');
        }
        catch(\Exception $e) {
            throw new exContainerCreateService(sprintf(
                'An exception was raised while creating (%s); no instance returned.'
                , $orgName
            ), $e->getCode(), $e);
        }

        # initialize retrieved service to match with defined implementation interface
        $this->_validateImplementation($orgName, $instance);
        return $instance;
    }

    /**
     * Builder Initializer Aggregate
     *
     * @return InitializerAggregate
     */
    function initializer()
    {
        if (!$this->initializer) {
            $this->initializer = new InitializerAggregate;

            // add default initializer:

            $thisContainer = $this;
            $this->initializer->addCallable(function($instance) use ($thisContainer) {
                if ($instance instanceof iServicesAware)
                    // Inject Service Container Inside
                    $instance->setServices($thisContainer);
            }, 10000);
        }

        return $this->initializer;
    }

    /**
     * Get Extend Service Name That This Service Extended Of That
     *
     * - if not extend any, return same service name
     *
     * @param string $serviceName
     *
     * @return string
     */
    function getExtended($serviceName)
    {
        while ($this->_hasExtendAliases($serviceName)) {
            $cAlias = $this->_normalizeServiceName($serviceName);
            $serviceName  = $this->aliases[$cAlias];
            ## check if we have alias to nested service
            if (strpos($serviceName, self::SEPARATOR) !== false)
                // we have an aliases that used as
                // share services between nested services
                // in form of "/filesystem/system/folder"
                // that mean, service is alias from "/filesystem/system/" for "folder" service
                break; ## so break iteration
        }

        return $serviceName;
    }

    /**
     * Set Alias Name For Registered Service
     *
     * - Alias point can be in form of "/filesystem/system/folder"
     *   that mean, alias name is extend from "/filesystem/system/"
     *   for "folder" service
     * - Aliases Can be set even if service not found
     *   or service added later
     *
     * @param string $newName        Alias
     * @param string $serviceOrAlias Registered Service/Alias
     *
     * @throws \Exception
     * @return $this
     */
    function extend($newName, $serviceOrAlias)
    {
        $throw = false;

        # If Alias Exists check for extended service to allow override or not.
        if ($this->_hasExtendAliases($newName)) {
            $extendService = $this->getExtended($newName);
            ## service from nested containers
            if (strstr($extendService, self::SEPARATOR)) {
                // TODO nested container
            } else {
                $cAlias = $this->_normalizeServiceName($extendService);
                if (!$this->services[$cAlias]->isAllowOverride())
                    $throw = array($newName, $extendService);
            }
        }

        # check for registered service with same alias name:
        $cAlias = $this->_normalizeServiceName($newName);
        if ($this->has($newName))
            // Alias is present as a service
            if (!$this->services[$cAlias]->isAllowOverride())
                $throw = array($newName, $newName);

        if ($throw)
            throw new \Exception(sprintf(
                'A service by the name (%s) is not allowed to be overridden by (%s).',
                $throw[0], $throw[1]
            ));


        $this->aliases[$cAlias] = $serviceOrAlias;
        return $this;
    }


    // Nested Containers:

    /**
     * Nest A Copy Of Container Within This Container
     *
     * @param Container   $container
     * @param string|null $namespace Container Namespace
     *
     * @return $this
     */
    function nest(Container $container, $namespace = null)
    {
        // Use Container Namespace if not provided as argument
        ($namespace !== null) ?: $namespace = $container->getNamespace();

        if ($namespace === null || $namespace === '')
            throw new \InvalidArgumentException(
                'Namespace can`t be empty.'
            );

        $cNamespace = $this->_normalizeServiceName($namespace);
        if (isset($this->__nestRight[$cNamespace]))
            throw new \InvalidArgumentException(sprintf(
                'Namespace (%s) is exists on container:%s'
                , $namespace , $this->getNamespace()
            ));

        $nestedCnt = clone $container;
        $nestedCnt->__nestLeft = $this; // set parent container
        $nestedCnt->setNamespace($namespace);
        $this->__nestRight[$cNamespace] = $nestedCnt;
        return $this;
    }

    /**
     * Retrieve Nested Container
     *
     * [code:]
     *   from('/') // means from first parent container
     *   from('nested/containers') // means nested>containers to this
     * [code]
     *
     * @param string $namespacePath
     *
     * @throws \Exception On Namespace not found
     * @return Container
     */
    function from($namespacePath)
    {
        if ($namespacePath === '')
            # from recursion calls
            return $this;

        // ..

        $cNamespace = $this->_normalizeNamespace($namespacePath);
        if (false === strstr($cNamespace, self::SEPARATOR)) {
            ## recursion fallback here !!
            if (!isset($this->__nestRight[$cNamespace]))
                throw new \Exception(sprintf(
                    'Namespace (%s) not found on (%s:%s).'
                    , $namespacePath , get_class($this), $this->getNamespace()
                ));

            return $this->__nestRight[$cNamespace];
        }

        // ..

        $namespacePath = rtrim($namespacePath, self::SEPARATOR);
        $brkNamespace  = explode(self::SEPARATOR, $namespacePath);

        ## /root/to/nested/namespace
        $cNamespace   = array_shift($brkNamespace);
        $cContainer   = $this;
        if ($cNamespace === '') {
            ## Start with / (separator)
            #- Goto Root Container
            while ($cContainer->__nestLeft)
                $cContainer = $cContainer->__nestLeft;
        }
        else $cContainer = $this->from($cNamespace); ## fallback

        ## fallback recursion from root parent
        return $cContainer->from(implode(self::SEPARATOR, $brkNamespace));
    }

    // ...

    /* Create Service Instance */
    protected function _createFromService(iContainerService $inService)
    {
        ErrorStack::handleError();

        # Initialize Service for dependencies etc.
        $this->_initializeServiceOrInstance($inService);

        # Retrieve Initialized Instance From Service
        $rInstance = $inService->createService();
        $this->_initializeServiceOrInstance($rInstance);

        if ($exception = ErrorStack::handleDone())
            throw $exception;

        return $rInstance;
    }

    /**
     * Initialize object with all parent nested initializers
     * @param mixed $inService instance created with service
     * @return mixed
     */
    function _initializeServiceOrInstance($inService)
    {
        # initialize with all parent namespaces, from root parent to current last
        $container    = $this;
        $initializers = array();
        while($container->__nestLeft) {
            $container = $container->__nestLeft;
            array_push($initializers, $container->initializer());
        }

        array_push($initializers, $this->initializer());
        foreach($initializers as $initializer)
            $initializer->initialize($inService);

        return $inService;
    }

    /**
     * validate interface against attained service instance
     * @param string $serviceName
     * @param object|mixed $instance
     * @throws \Exception
     */
    protected function _validateImplementation($serviceName, $instance)
    {
        if (false === $implement = $this->hasImplementation($serviceName))
            ## we have not defined implementation, nothing to do
            return;

        // ..

        $throw = true;
        if (interface_exists($implement))
            ## check implementation of given interface
            $throw = !(in_array(class_implements($instance), $implement));
        elseif (class_exists($implement))
            ## check implementation of extended class
            $throw = !(is_subclass_of($instance, $implement));

        if ($throw)
            throw new \Exception(sprintf(
                'Service with name (%s) must implement (%s); given: %s'
                , $serviceName, $implement, \Poirot\Std\flatten($instance)
            ));
    }
    
    /**
     * Determine if we have an alias name
     * that extend service
     *
     * @param  string $alias
     * @return bool
     */
    protected function _hasExtendAliases($alias)
    {
        $cAlias = $this->_normalizeServiceName($alias);
        return isset($this->aliases[$cAlias]);
    }
    
    /**
     * Normalize Given Name
     *
     * - the name can't contains separate(/) string
     * - cant contains any space
     * - names stored as all lowercase
     *
     * @param string $name
     *
     * @throws \Exception
     * @return string
     */
    protected function _normalizeServiceName($name)
    {
        if (!is_string($name) || $name === '' )
            throw new \Exception(sprintf(
                'Name must be a none empty string, you injected (%s).'
                , \Poirot\Std\flatten($name)
            ));

        if (isset($this->_c_normalizedNames[$name]))
            return $this->_c_normalizedNames[$name];

        $canonicalName = strtolower(
            strtr($name, array(' ' => '', '\\' => self::SEPARATOR))
        );

        if (strstr($name, self::SEPARATOR) !== false)
            throw new \Exception(sprintf(
                'Service Or Alias Name Cant Contains Separation String (%s).'
                , self::SEPARATOR
            ));

        return $this->_c_normalizedNames[$name] = $canonicalName;
    }

    /**
     * Normalize Given Name
     *
     * - cant contains any space
     * - names stored as all lowercase
     *
     * @param string $name
     *
     * @throws \Exception
     * @return string
     */
    protected function _normalizeNamespace($name)
    {
        if (isset($this->_c_normalizedNames[$name]))
            return $this->_c_normalizedNames[$name];

        $canonicalName = strtolower(
            strtr($name, array(' ' => '', '\\' => self::SEPARATOR))
        );

        return $this->_c_normalizedNames[$name] = $canonicalName;
    }
}
