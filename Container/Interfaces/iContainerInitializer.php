<?php
namespace Poirot\Ioc\Container\Interfaces;

use Poirot\Ioc\Container;


interface iContainerInitializer
{
    /**
     * Initialize Service
     *
     * @param mixed     $instance  Service
     * @param Container $target    Container itself
     *
     * @return mixed
     */
    function initialize($instance, $target = null);

    /**
     * Used To Bind Initializer
     *
     * @return int
     */
    function getPriority();
}
