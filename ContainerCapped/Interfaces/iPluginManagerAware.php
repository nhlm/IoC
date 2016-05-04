<?php
namespace Poirot\Container\Interfaces\Plugins;

use Poirot\Container\Plugins\aContainerCapped;

interface iPluginManagerAware
{
    /**
     * Set Plugins Manager
     *
     * @param aContainerCapped $plugins
     *
     * @return $this
     */
    function setPluginManager(aContainerCapped $plugins);
}
