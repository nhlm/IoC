<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Ioc\Container\Interfaces\iServiceFeatureAggregate;
use Poirot\Ioc\instance;
use Poirot\Loader\Interfaces\iLoader;
use Poirot\Loader\Interfaces\Respec\iLoaderAware;
use Poirot\Loader\LoaderAggregate;
use Poirot\Loader\LoaderMapResource;


class ServicePluginLoader
    extends aServiceAggregate
    implements iServiceFeatureAggregate
    , iLoaderAware
{
    /** @var string Current Service Name That Respond To It */
    protected $currentService;
    
    protected $lastCreatedService;

    /** @var LoaderAggregate Global Default Resolver */
    static protected $default_resolver;

    /** @var iLoader */
    protected $_resolver;
    protected $_c_resolved = array(
        # 'serviceName' => $resolved
    );


    /**
     * @override
     * Indicate to allow overriding service
     * with another service
     *
     * @var boolean
     */
    protected $allowOverride = true;


    /**
     * ServicePluginLoader constructor.
     * @param array|string $nameOsetter
     * @param array        $setter
     */
    function __construct($nameOsetter, array $setter = array())
    {
        // Arrange Setter Priorities
        $this->putBuildPriority(array(
            'resolver',
            'resolver_options'
        ));

        parent::__construct($nameOsetter, $setter);
    }

    /**
     * Determine Which Can Create Service With Given Name?
     *
     * @param string $serviceName
     *
     * @return boolean
     */
    function canCreate($serviceName)
    {
        return (boolean) $this->_resolveTo($serviceName);
    }

    /**
     * Create Service
     *
     * @param string $serviceName
     *
     * @return mixed
     * @throws \Exception
     */
    function newService($serviceName = null)
    {
        ($serviceName) ?: $serviceName = $this->currentService;
        if ($serviceName === null)
            throw new \Exception('Service name is empty; use ::withService() method.');


        if (false === $service = $this->_resolveTo($serviceName))
            throw new \Exception(sprintf('Cant Resolve To Plugin Service (%s).', $serviceName));

        if ( is_string($service) && class_exists($service) ) {
            // Resolved Class Name
            $options = \Poirot\Std\cast($this->optsData())->toArray();
            $service = \Poirot\Ioc\newInitIns(new instance($service, $options));

        }

        return $service;
    }
    
    /**
     * Get Last Created Service
     *
     * @return mixed|null
     */
    function getLastCreatedService()
    {
        return $this->lastCreatedService;
    }


    // iLoaderAware

    /**
     * Set Loader Resolver
     *
     * @param iLoader $resolver
     *
     * @return $this
     * @throws \Exception
     */
    function setResolver(iLoader $resolver)
    {
        $this->_resolver = $resolver;
        return $this;
    }


    // Options:

    /**
     * Proxy to Resolver Options
     * @param array $options
     * @return $this
     */
    function setResolverOptions($options)
    {
        if (method_exists($this->_resolver(), 'with'))
            $this->_resolver()->with($options);

        return $this;
    }

    /**
     * @override
     * @inheritdoc
     */
    function setAllowOverride($allow)
    {
        throw new \Exception('Override services is always granted.');
    }



    // ..


    /**
     * Default Loader Resolver
     *
     * @return LoaderAggregate
     */
    static function Loader()
    {
        if (!self::$default_resolver) {
            $resolver = new LoaderAggregate;
            $resolver->attach(new LoaderMapResource, 100);
            self::$default_resolver = $resolver;
        }

        return self::$default_resolver;
    }


    /** @return iLoader */
    protected function _resolver()
    {
        if (!$this->_resolver)
            $this->_resolver = self::Loader();

        return $this->_resolver;
    }
    
    protected function _resolveTo($serviceName)
    {
        if (!array_key_exists($serviceName, $this->_c_resolved)) {
            $this->_c_resolved[$serviceName] = $this->_resolver()->resolve($serviceName);
        }
        
        return $this->_c_resolved[$serviceName];
    }
}
