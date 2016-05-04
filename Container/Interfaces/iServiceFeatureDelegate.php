<?php
namespace Poirot\Ioc\Container\Interfaces;

use Poirot\Ioc\Container;

/**
 * Container Service that Implement this Feature every time
 * that register into Container delegate method will call to
 * prepare into Container.
 * 
 * @see Container::set 
 */
interface iServiceFeatureDelegate
{
    function delegate(Container $container);
}
