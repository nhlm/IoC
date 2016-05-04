<?php
namespace Poirot\Container\Interfaces\Plugins;

use Poirot\Container\Plugins\aContainerCapped;

interface iPluginManagerProvider
{
    /**
     * Get Plugins Manager
     *
     * @return aContainerCapped
     */
    function getPluginManager();
}
