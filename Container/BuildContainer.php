<?php
namespace Poirot\Ioc\Container;

use Poirot\Std\ConfigurableSetter;
use Poirot\Std\Interfaces\Pact\ipConfigurable;
use Poirot\Std\Interfaces\Pact\ipOptionsProvider;

use Poirot\Ioc\Container;
use Poirot\Ioc\Container\Service\ServiceInstance;
use Poirot\Ioc\Container\Interfaces\iContainerInitializer;
use Poirot\Ioc\Container\Interfaces\iContainerService;


class BuildContainer
    extends ConfigurableSetter
{
    const INST = '_class_';
    const NAME = ':name';

    protected $namespace;
    protected $services        = array(
        # 'GrantResponder' => \Module\OAuth2\Services\ServiceGrantResponder::class
    );
    protected $extends         = array();
    protected $initializers    = array();
    protected $nested          = array();
    protected $implementations = array();

    /**
     * @override Force to throw Exception on invalid Options
     * @inheritdoc
     */
    function with(array $options, $throwException = true)
    {
        parent::with($options, $throwException);
    }
    
    /**
     * Configure container manager
     *
     * @param Container $container
     *
     * @throws \Exception
     * @return void
     */
    function build(Container $container)
    {
        if (!$container instanceof Container)
            throw new \Exception(sprintf(
                'Container must instanceof "ContainerManager", you given "%s".'
                , (is_object($container)) ? get_class($container) : gettype($container)
            ));

        // ORDER IS MANDATORY

        // Namespace:
        $this->_buildNamespace($container);

        // Interfaces:
        $this->_buildImplementation($container);

        // Initializer:
        // maybe used while Creating Services
        $this->_buildInitializer($container);

        // Nested:
        $this->_buildNested($container);

        // Aliases:
        $this->_buildExtend($container);

        // Service:
        $this->_buildService($container);
    }


    // Setter Methods

    /**
     * 'namespace' => 'sysdir'
     *
     * @param string $namespace
     */
    function setNamespace($namespace)
    {
        $this->namespace = (string) $namespace;
    }

    /**
     * 'implementations' => [
     *    'log'     => Poirot\Logger\Interfaces\iLogger::class, // interface
     *    'logs'    => Poirot\Logger\Logs::class,               // class name
     *    'modules' => new P\Ioc\Container                      // class object
     *
     * @param array $options
     *
     * @return $this
     */
    function setImplementations($options)
    {
        foreach ($options as $key => $v) {
            if (!is_int($key))
                $v = array($key => $v);

            $this->addImplementation($v);
        }

        return $this;
    }

    function addImplementation($implementation)
    {
        $this->_importValueTo($this->implementations, $implementation);
        return $this;
    }

    /**
     * 'services'   => [
     *    new ServiceFactory('serviceName', $callable),     // must be iContainerService
     *    'Path\To\ServiceImplementation',                  // must be iContainerService
     *    'env' => P\Std\Environment\EnvDevelopment::class, // register internally with ServiceInstance
     *
     *    # Implementation of iContainerService or any object
     *    'ServiceFactory' => ... or
     *    'Path\To\ServiceImplementation' => [':name' => 'serviceName', 'setter' => 'value' ..,
     *    'ServiceName' => [':class' => 'ClassOrServiceImplementation' .. or
     *    'Path\To\ClassName' => [':name' => 'ClassOrServiceImplementation', 'setter' => $value, ..
     *
     * @param array $services
     *
     * @return $this
     */
    function setServices($services)
    {
        foreach ($services as $key => $v) {
            if (!is_int($key))
                $v = array($key => $v);

            $this->addService($v);
        }

        return $this;
    }

    function addService($service)
    {
        $this->_importValueTo($this->services, $service);
        return $this;
    }

    /**
     * 'extends' => [
     *    'newName' => 'serviceOrAlias',
     *
     * @param array $options
     *
     * @return $this
     */
    function setExtends($options)
    {
        foreach ($options as $key => $v) {
            if (!is_int($key))
                $v = array($key => $v);

            $this->addExtend($v);
        }


        return $this;
    }

    function addExtend($extend)
    {
        $this->_importValueTo($this->extends, $extend);
        return $this;
    }

    /**
     * 'initializers' => [
     *    $priority => callable,
     *    iInitializer,
     *    [
     *       'priority'    => 10,
     *       'initializer' => callable | iInitializer, // iInitializer priority will override
     *    ]
     *
     * @param array $options
     *
     * @return $this
     */
    function setInitializers($options)
    {
        foreach ($options as $key => $v) {
            if (!(is_array($v) || $v instanceof iContainerInitializer))
                $v = array($key => $v);

            $this->addInitializer($v);
        }

        return $this;
    }

    function addInitializer($initializer)
    {
        $this->_importValueTo($this->initializers, $initializer);
        return $this;
    }

    /**
     * 'nested' => [
     *    'namespace' => new ContainerManager,
     *     new ContainerManager()
     *
     *    'namespace' => $builderArrayOption, // container builder option
     *    $builderArrayOption,                // container builder option
     *
     * @param array $options
     *
     * @return $this
     */
    function setNested($options)
    {
        foreach ($options as $key => $v) {
            if (!is_int($key))
                $v = array($key => $v);

            $this->addNested($v);
        }

        return $this;
    }

    function addNested($nested)
    {
        $this->_importValueTo($this->nested, $nested);
        return $this;
    }


    // Options build action:

    protected function _buildNamespace(Container $container)
    {
        if ($this->namespace)
            $container->setName($this->namespace);
    }

    protected function _buildImplementation(Container $container)
    {
        if (!$this->implementations)
            return;

        foreach ($this->implementations as $serviceName => $interface)
            $container->setImplementation($serviceName, $interface);
    }

    protected function _buildInitializer(Container $container)
    {
        if (empty($this->initializers))
            return;

        foreach ($this->initializers as $priority => $initializer) {
            if ($initializer instanceof iContainerInitializer)
                // [.. [ iInitializer, ...], ...]
                $priority = null;
            elseif (is_array($initializer)) {
                // [ .. [ 10 => ['priority' => 10, 'initializer' => ...], ...]
                $priority    = (isset($initializer['priority'])) ? $initializer['priority'] : $priority;
                $initializer = (!isset($initializer['initializer'])) ?: $initializer['initializer'];
            }

            if (is_callable($initializer))
                $container->initializer()->addCallable($initializer, $priority);
            elseif ($initializer instanceof iContainerInitializer)
                $container->initializer()->addInitializer(
                    $initializer
                    , ($priority === null) ? $initializer->getPriority() : $priority
                );
        }
    }

    protected function _buildNested(Container $container)
    {
        foreach($this->nested as $namespace => $nestedOptions)
        {
            if (!is_string($namespace))
                ## nested as options [options, ..]
                $namespace = $nestedOptions->getName();

            $namespace = (string) $namespace;

            if (false === $hasNested = $container->from($namespace)) {
                if (is_array($nestedOptions)) {
                    /// Using same container as parent while nesting ....
                    //- when nestedOptions is settings array
                    $nest = get_class($container);
                    $nest = new $nest;

                    $builder = new BuildContainer($nestedOptions);
                    $builder->build($nest);
                } else
                    $nest = $nestedOptions;

                $container->nest($nest, $namespace);

            } else {
                if (!is_array($nestedOptions))
                    throw new \InvalidArgumentException(sprintf(
                        'Nested container (%s) is exists and cant be override; instead use builder array.'
                        , $namespace
                    ));

                $builder = new BuildContainer($nestedOptions);
                $builder->build($hasNested);
            }
        }
    }

    protected function _buildExtend(Container $container)
    {
        foreach($this->extends as $newName => $serviceOrAlias)
            $container->extend($newName, $serviceOrAlias);
    }

    /**
     * @param Container $container
     * @throws \Exception
     */
    protected function _buildService(Container $container)
    {
        foreach($this->services as $key => $v)
        {
            $name     = null;
            $class    = null;   // Object, Object:Service, 'ClassName', 'ServiceFactory'
            $instance = null;
            $options  = array();

            if (is_string($key) && is_array($v))
            {
                // [ 'ServiceName' => [':class' => 'ClassOrServiceImplementation', 'setter' => $value, .. or
                // [ 'Path\To\ClassName' => [':name' => 'serviceName', 'setter' => $value, ..
                // [ 'ServiceFactory' => .. or
                // [ 'Path\To\ServiceImplementation' => [':name' => 'serviceName', 'setter' => 'value' ..,
                $class = $key;

                if (array_key_exists(self::INST, $v)) {
                    // [ 'service_name' => [ ':class' => ...
                    $v[self::NAME] = $key;
                    $class = $v[self::INST];
                    unset($v[self::INST]);
                }

                if (array_key_exists(self::NAME, $v)) {
                    $name = $v[self::NAME];
                    unset($v[self::NAME]);
                }

                if (!class_exists($class) && strstr($class, '\\') === false) {
                    // [ 'ServiceFactory' => ...
                    // try to achieve as default services
                    $class = '\\'.__NAMESPACE__.'\\Service\\'.ucfirst($class);
                }

                $options = $v;
            }
            else
            {
                // [ new ServiceFactory('serviceName', $callable),
                // [ 'Path\To\ServiceImplementation',
                // [ 'env' => P\Std\Environment\EnvDevelopment::class,
                $class   = $v;
                $name    = $key;
            }

            // [ new ServiceFactory('serviceName', $callable),
            $instance = $class;

            if (!is_object($class))
            {
                // [ 'Path\To\ServiceImplementation',
                // [ 'env' => P\Std\Environment\EnvDevelopment::class,
                if (!class_exists($class)) {
                    (!is_int($name)) ?: $name = 'no-name (iCService)';
                    throw new \Exception("Class '{$class}' not found to build service '{$name}'.");
                }

                $rClass = new \ReflectionClass($class);
                if ($rClass->implementsInterface('Poirot\Ioc\Container\Interfaces\iContainerService')) {
                    if ($rClass->hasMethod('__construct')) {
                        // Resolve Arguments to constructor and create new instance
                        $rMethod  = $rClass->getMethod('__construct');
                        $resolved = \Poirot\Std\Invokable\resolveArgsForReflection($rMethod, $options, false);
                        $instance  = $rClass->newInstanceArgs($resolved);

                        // let remind options used as features like configurable
                        // $options = @array_diff($options, $resolved);
                    } else {
                        // service without constructor
                        $instance = new $class;
                    }

                    ## Inject Dependencies:
                    if ($instance instanceof ipOptionsProvider && !empty($options))
                        $instance->optsData()->import($options);

                    if ($instance instanceof ipConfigurable && !empty($options))
                        $instance->with($options);

                    // set name if given otherwise use ::getName method of iCService
                    (is_int($name)) ?: $instance->setName($name);
                }
            }

            ## Instance Service helper:
            if (!$instance instanceof iContainerService) {
                // [ new ServiceFactory('serviceName', $callable),
                if (empty($name) || is_int($name))
                    throw new \InvalidArgumentException(sprintf(
                        "%s Service Name '%s' not recognized for (%s)."
                        , $this->namespace, $key, $v
                    ));

                // TODO avoid construct with options; instead use setter methods
                $instance = new ServiceInstance(array(
                    'name'    => $name,
                    'service' => $instance,
                    'options' => $options,
                ));

                /*
                 * Fatal error: Maximum function nesting level
                $instance->setName($name);
                $instance->setService($instance);
                $instance->optsData()->import($options);*/

            }

            $container->set($instance);
        }
    }

    function _importValueTo(&$array, $data)
    {
        if (is_array($data) && array_values($data) !== $data)
            // assoc array
            $array = array_merge($array, $data);
        else
            array_push($array, $data);
    }
}
