<?php
namespace Poirot\Container\Plugins;

use Poirot\Container\Container;
use Poirot\Container\Exception\ContainerInvalidPluginException;
use Poirot\Container\Interfaces\iContainerService;
use Poirot\Container\Service\InstanceService;
use Poirot\Loader\Interfaces\iLoader;
use Poirot\Loader\Interfaces\iLoaderProvider;
use Poirot\Loader\ResourceMapResolver;

abstract class AbstractPlugins extends Container
    implements iLoaderProvider
{
    protected $loader_resources = [
        # 'canonicalized_name' => string(ClassName) // class can be instance of iCService or not
        # 'canonicalized_name' => iCService|Object
    ];

    /** @var iLoader|ResourceMapResolver */
    protected $resolver;

    /**
     * Validate Plugin Instance Object
     *
     * @param mixed $pluginInstance
     *
     * @throws ContainerInvalidPluginException
     * @return void
     */
    abstract function validatePlugin($pluginInstance);

    /**
     * Retrieve a registered instance
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws ContainerInvalidPluginException
     * @return mixed
     */
    function get($serviceName, $invOpt = [])
    {
        $this->__attainServiceFromLoader($serviceName);
        
        $return = parent::get($serviceName, $invOpt);
        $this->validatePlugin($return);

        return $return;
    }

    /**
     * Retrieve a fresh instance of service
     *
     * @param string $serviceName Service name
     * @param array $invOpt Invoke Options
     *
     * @throws \Exception
     * @return mixed
     */
    function fresh($serviceName, $invOpt = [])
    {
        $this->__attainServiceFromLoader($serviceName);

        $return = parent::fresh($serviceName, $invOpt);
        $this->validatePlugin($return);

        return $return;
    }

    protected function __attainServiceFromLoader($serviceName)
    {
        if (parent::has($serviceName))
            return;


        $serviceName = $this->__canonicalizeName($serviceName);

        ## maybe resolved as loader plugin
        if ($resolved = $this->resolver()->resolve($serviceName)) {
            $service = $resolved;
            if (is_string($resolved)) {
                if (!class_exists($resolved))
                    throw new \RuntimeException(sprintf(
                        'Class (%s) not found for (%s) service with Loader Resource.'
                        ,$resolved ,$serviceName
                    ));

                $service = new $resolved();
            }

            if (!$service instanceof iContainerService)
                $service = new InstanceService($serviceName, $service);

            ### set service so be can retrieved later
            $this->set($service);
        }
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
        $has = parent::has($serviceName);
        if ($has)
            return $has;

        return (boolean) $this->resolver()->resolve($serviceName);
    }


    /**
     * List Whole Registered Plugins
     *
     * @return array[string]
     */
    function listServices()
    {
        return array_merge(array_keys($this->loader_resources), array_keys($this->services));
    }


    /**
     * @override
     *
     * Nest A Copy Of Container Within This Container
     *
     * @param Container   $container
     * @param string|null $namespace Container Namespace
     *
     * @return $this
     */
    function nest(Container $container, $namespace = null)
    {
        if (!$container instanceof $this)
            throw new \InvalidArgumentException(sprintf(
                'Only can nest with same type pluginManager object, given "%s".'
                , get_class($container)
            ));

        return parent::nest($container, $namespace);
    }


    // Implement Loader:

    /**
     * Loader Resolver
     *
     * ! it may more useful when store resource
     *   with canonicalized names.
     *
     * @return ResourceMapResolver|iLoader
     */
    function resolver()
    {
        if (!$this->resolver)
            $this->resolver = new ResourceMapResolver($this->loader_resources);

        return $this->resolver;
    }
}
