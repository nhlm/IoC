<?php
namespace Poirot\Container\Interfaces;

interface iContainerInitializer
{
    /**
     * Initialize Service
     *
     * @param mixed $service Service
     *
     * @return mixed
     */
    function initialize($service);

    /**
     * Used To Bind Initializer
     *
     * @return int
     */
    function getPriority();
}
