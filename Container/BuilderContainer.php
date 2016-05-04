<?php
namespace Poirot\Ioc\Container;

use Poirot\Std\ConfigurableSetter;
use Poirot\Std\Interfaces\Struct\iData;
use Poirot\Ioc\Container;
use Poirot\Ioc\Container\Service\ServiceInstance;
use Poirot\Ioc\Container\Interfaces\iContainerInitializer;
use Poirot\Ioc\Container\Interfaces\iContainerService;

/**
    'services'   => [
        new FactoryService(['name' => 'sysdir',
            'delegate' => function() {
                // Delegates will bind to service object as closure method
                $sc = $this->getServiceContainer();
                return $sc->from('files')->get('folder');
            },
            'allow_override' => false
        ]),
 *
        // or
        # Service Name
        'dev.lamp.status' => [
                          # or regular class object. (will create instance from factoryService)
            '_class_' => 'FactoryService', # Prefixed Internaly with Container namespace
                                           # or full path 'Namespaces\Path\To\Service' class
            // ... options setter of service class .........................................
            'delegate' => function() {
                # Delegates will bind to service object as closure method
                @var FactoryService $this
                $sc = $this->getServiceContainer();
                return $sc->from('files')->get('folder');
            },
            'allow_override' => false
        ],
 *
 *      'HomeInfo' => 'Application\Actions\HomeInfo',

 *      // or
        # just a iCService Implementation,
        # service name are included in class
        'ClassName',                      # Prefixed Internaly with Container namespace
                                          # or full path 'Namespaces\Path\To\Service' class
        // You Can Set Options
        # Implementation of iCService or any object
 *      'ClassName' => ['_name_' => 'serviceName', 'option' => 'value' ],
 *
    ],
 */
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
    private function _buildService(Container $container)
    {
        if (!empty($this->services))
            foreach($this->services as $key => $service) {
                if (is_string($key) && is_array($service))
                {
                    if (array_key_exists('_class_', $service)) {
                        // *** [ 'service_name' => [ '_class_' => 'serviceClass', /* options */ ], ...]
                        // ***
                        $service['name'] = $key;
                        $key             = $service['_class_'];
                        unset($service['_class_']);
                    }
                    // *** else: [ 'serviceClass' => [ /* options */ ], ...]
                    // ***
                    if (!class_exists($key) && strstr($key, '\\') === false)
                        // this is FactoryService style,
                        // must prefixed with own namespace
                        $key = '\\'.__NAMESPACE__.'\\Service\\'.$key;

                    $class = $key;
                } else
                {
                    // *** Looking For Class 'Path\To\Class'
                    // ***
                    $class   = $service;
                    $name    = $key;
                    $service = array(); // service without options
                }

                if (is_object($class))
                    $instance = $class;
                else {
                    if (!class_exists($class))
                        throw new \Exception($this->namespace.": Service '$key' not found as Class Name.");

                    $instance = new $class;
                }

                if ($instance instanceof iContainerService || $instance instanceof iData)
                    // TODO container options
                    $instance->import($service);

                if (!$instance instanceof iContainerService) {
                    if (!array_key_exists('name', $service) && !isset($name))
                        throw new \InvalidArgumentException($this->namespace.": Service '$key' not recognized.");

                    $name     = (isset($service['_name_'])) ? $service['_name_'] : $name;
                    $instance = new ServiceInstance($name, $instance);
                }

                $container->set($instance);
            }
    }
}
