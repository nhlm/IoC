<?php
namespace Poirot\Ioc\Container\Interfaces;

use Poirot\Ioc\Container;

/**
 * Container Service that Implement this Feature every time
 * that register into Container delegate method will call to
 * prepare with Container.
 * 
 * @see Container::set 
 */
interface iServiceDelegateFeature
{
    function delegate(Container $container);
}
