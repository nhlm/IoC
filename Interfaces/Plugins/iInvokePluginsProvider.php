<?php
namespace Poirot\Container\Interfaces\Plugins;

use Poirot\Container\Plugins\PluginsInvokable;

interface iInvokePluginsProvider
{
    /**
     * Plugin Manager
     *
     * @return PluginsInvokable
     */
    function plg();
}
