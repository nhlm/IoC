<?php
namespace Poirot\Container\Interfaces;

interface iContainerInitializer
{
    /**
     * Initialize Service
     *
     * @param mixed $instance Service
     *
     * @return mixed
     */
    function initialize($instance);

    /**
     * Used To Bind Initializer
     *
     * @return int
     */
    function getPriority();
}
