<?php
namespace Poirot\Ioc\Container\Interfaces;

use Poirot\Std\Interfaces\Pact\ipOptionsProvider;

interface iContainerService
    extends ipOptionsProvider
{
    /**
     * Set Service Name
     *
     * @param string $name Service Name
     *
     * @return $this
     */
    function setName($name);

    /**
     * Get Service Name
     *
     * @return string
     */
    function getName();

    /**
     * Create Service
     *
     * @return mixed
     */
    function newService();

    
    // options:
    
    /**
     * Set Allow Override By Service
     *
     * @param boolean $allow Flag
     *
     * @return $this
     */
    function setAllowOverride($allow);

    /**
     * Get allow override
     *
     * @return boolean
     */
    function isAllowOverride();
}
