<?php
namespace Poirot\Container\Interfaces;

use Poirot\Std\Interfaces\Struct\iOptionsData;

interface iCService extends iOptionsData
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
    function createService();

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
    function getAllowOverride();
}
