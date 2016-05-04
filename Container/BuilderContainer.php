<?php
namespace Poirot\Ioc\Container;

use Poirot\Std\ConfigurableSetter;
use Poirot\Std\Interfaces\Pact\ipOptionsProvider;

use Poirot\Ioc\Container;
use Poirot\Ioc\Container\Service\ServiceInstance;
use Poirot\Ioc\Container\Interfaces\iContainerInitializer;
use Poirot\Ioc\Container\Interfaces\iContainerService;

class BuilderContainer
    extends ConfigurableSetter
{
    protected $namespace;
    protected $services        = array();
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
    public function setNamespace($namespace)
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
     */
    public function setImplementations($options)
    {
        $this->implementations = $options;
    }

    /**
     * 'services'   => [
     *    new ServiceFactory('serviceName', $callable),     // must be iContainerService
     *    'Path\To\ServiceImplementation',                  // must be iContainerService
     *    'env' => P\Std\Environment\EnvDevelopment::class, // register internally with ServiceInstance
     *
     *    # Implementation of iCService or any object
     *    'ServiceFactory' => ... or
     *    'Path\To\ServiceImplementation' => [':name' => 'serviceName', 'setter' => 'value' ..,
     *    'ServiceName' => [':class' => 'ClassOrServiceImplementation' .. or
     *    'Path\To\ClassName' => [':name' => 'ClassOrServiceImplementation', 'setter' => $value, ..
     *
     * @param array $services
     */
    public function setServices($services)
    {
        $this->services = $services;
    }

    /**
     * 'extends' => [
     *    'newName' => 'serviceOrAlias',
     *
     * @param array $options
     */
    public function setExtends($options)
    {
        $this->extends = $options;
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
     */
    public function setInitializers($options)
    {
        $this->initializers = $options;
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
     */
    public function setNested($options)
    {
        $this->nested = $options;
    }


    // Options build action:

    protected function _buildNamespace(Container $container)
    {
        if ($this->namespace)
            $container->setNamespace($this->namespace);
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
        if (empty($this->nested))
            return;

        foreach($this->nested as $namespace => $nest) {
            if (is_array($nest))
                $nest = new Container(new BuilderContainer($nest));

            
            if (!is_string($namespace))
                ## nested as options [options, ..]
                $namespace = $nest->getNamespace();

            $container->nest($nest, $namespace);
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
                // [ 'ServiceName' => [':class' => 'ClassOrServiceImplementation' .. or
                // [ 'Path\To\ClassName' => [':name' => 'ClassOrServiceImplementation', 'setter' => $value, ..
                // [ 'ServiceFactory' => .. or
                // [ 'Path\To\ServiceImplementation' => [':name' => 'serviceName', 'setter' => 'value' ..,
                $class = $key;

                if (array_key_exists(':class', $v)) {
                    // [ 'service_name' => [ ':class' => ...
                    $v[':name'] = $key;
                    $class = $v[':class'];
                    unset($v[':class']);
                }

                if (array_key_exists(':name', $v)) {
                    $name = $v[':name'];
                    unset($v[':name']);
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

            if (is_object($class))
                // [ new ServiceFactory('serviceName', $callable),
                $instance = $class;
            else {
                // [ 'Path\To\ServiceImplementation',
                // [ 'env' => P\Std\Environment\EnvDevelopment::class,
                if (!class_exists($class))
                    throw new \Exception($this->namespace.": Service '{$key}' not found as Class Name.");

                $instance = new $class;
            }

            if ($instance instanceof ipOptionsProvider && !empty($options))
                ## Options Provided Pact
                $instance->optsData()->import($options);

            if (!$instance instanceof iContainerService) {
                // [ new ServiceFactory('serviceName', $callable),
                if ($name === null)
                    throw new \InvalidArgumentException($this->namespace.": Service '$key' not recognized.");

                $instance = new ServiceInstance($name, $instance);
            }

            $container->set($instance);
        }
    }
}
