<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Std\ConfigurableSetter;
use Poirot\Std\Struct\aDataOptions;
use Poirot\Std\Struct\DataOptionsOpen;
use Poirot\Std\Interfaces\Struct\iDataOptions;
use Poirot\Ioc\Container;
use Poirot\Ioc\Interfaces\iContainer;
use Poirot\Ioc\Interfaces\Respec\iServicesComplex;
use Poirot\Ioc\Container\Interfaces\iContainerService;

abstract class aContainerService
    extends    ConfigurableSetter
    implements iContainerService
    , iServicesComplex
{
    /**
     * @var array
     * @see Container::initializer
     * @see Container::get
     */
    public $invoke_options;

    /** @var string Service Name */
    protected $name;

    /**
     * Indicate to allow overriding service
     * with another service
     *
     * @var boolean
     */
    protected $allowOverride = true;

    /** @var DataOptionsOpen */
    protected $options;

    /**
     * implement iCServiceAware
     * @var Container Injected Container
     */
    protected $sc;


    /**
     * aContainerService constructor.
     *
     * [code:]
     *   new Service('serviceName', ['allow_override' => false])
     *   new Service(['name' => 'serviceName', 'allow_override' => false])
     * [code]
     * 
     * @param string|array $nameOsetter Service name Or Setter Options
     * @param array        $setter      Setter Options
     */
    function __construct($nameOsetter, array $setter = array())
    {
        if (is_string($nameOsetter))
            $setter['name'] = $nameOsetter;

        if (is_array($nameOsetter))
            $setter = $nameOsetter;

        parent::__construct($setter);
    }

    /**
     * Create Service
     *
     * @return mixed
     */
    abstract function createService();

    /**
     * Set Service Name
     *
     * @param string $name Service Name
     *
     * @return $this
     */
    function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Get Service Name
     *
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Set Allow Override By Service
     *
     * @param boolean $allow Flag
     *
     * @return $this
     */
    function setAllowOverride($allow)
    {
        $this->allowOverride = (boolean) $allow;
        return $this;
    }

    /**
     * Get allow override
     *
     * @return boolean
     */
    function isAllowOverride()
    {
        return $this->allowOverride;
    }

    /**
     * Proxy call for Options Setter Builder
     * @param mixed $builder Builder Options
     * @return $this
     */
    function setOptions($builder)
    {
        $this->optsData()->import($builder);
        return $this;
    }

    // Implement iServicesComplex: (So Service Have Access To Other Services)

    //  Container Initializer Also Affect On Service Object,
    //- it means we check for Interfaces implementation and by
    //- default container behavior inject services container into it.

    /**
     * Set Service Container
     * @param iContainer $container
     * @return $this
     */
    function setServices(iContainer $container)
    {
        $this->sc = $container;
        return $this;
    }

    /**
     * Get Service Container
     *
     * @return iContainer
     */
    function services()
    {
        return $this->sc;
    }


    // Implement ipOptionsProvider:

     /**
      * @return iDataOptions
      */
     function optsData()
     {
         if (!$this->options)
             $this->options = self::newOptsData();

         return $this->options;
     }

     /**
      * Get An Bare Options Instance
      *
      * ! it used on easy access to options instance
      *   before constructing class
      *   [php]
      *      $opt = Filesystem::optionsIns();
      *      $opt->setSomeOption('value');
      *
      *      $class = new Filesystem($opt);
      *   [/php]
      *
      * @param null|mixed $builder Builder Options as Constructor
      *
      * @return iDataOptions
      */
     static function newOptsData($builder = null)
     {
         $opt = new DataOptionsOpen;
         return $opt->import($builder);
     }
}
