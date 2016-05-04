<?php
namespace Poirot\Ioc;

use Poirot\Ioc\Container\Interfaces\iContainerService;
use Poirot\Ioc\Container\Service\ServiceInstance;
use Poirot\Ioc\Plugins\Exception\exContainerInvalidPlugin;
use Poirot\Loader\Interfaces\iLoader;
use Poirot\Loader\LoaderMapResource;

/**
 * Container that extends Capped Container
 * can have just validated services inside
 * this validated can achieve the services
 * from exact type(s).
 *
 * e.g View Renderer just have iRenderer
 * implementation as registered service.
 */
abstract class aContainerCapped
    extends Container
{
    protected $loader_resources = array(
        # 'canonicalized_name' => string(ClassName) // class can be instance of iCService or not
        # 'canonicalized_name' => iCService|Object
    );

    /** @var iLoader|LoaderMapResource */
    protected $resolver;

    /**
     * Validate Plugin Instance Object
     *
     * @param mixed $pluginInstance
     *
     * @throws exContainerInvalidPlugin
     * @return void
     */
    abstract function validatePlugin($pluginInstance);

    /**
     * Retrieve a registered instance
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws exContainerInvalidPlugin
     * @return mixed
     */
    function get($serviceName, $invOpt = array())
    {
        $this->_attainServiceFromLoader($serviceName);
        
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
    function fresh($serviceName, $invOpt = array())
    {
        $this->_attainServiceFromLoader($serviceName);

        $return = parent::fresh($serviceName, $invOpt);
        $this->validatePlugin($return);
        return $return;
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
     * @return LoaderMapResource|iLoader
     */
    function resolver()
    {
        if (!$this->resolver)
            $this->resolver = new LoaderMapResource($this->loader_resources);

        return $this->resolver;
    }
    
    
    // ..

    protected function _attainServiceFromLoader($serviceName)
    {
        if (parent::has($serviceName))
            return;


        $serviceName = $this->_normalizeName($serviceName);

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
                $service = new ServiceInstance($serviceName, $service);

            ### set service so be can retrieved later
            $this->set($service);
        }
    }
}
