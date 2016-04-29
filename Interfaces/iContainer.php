<?php
namespace Poirot\Container\Interfaces;

use Poirot\Container\Exception\ContainerCreateServiceException;
use Poirot\Container\Exception\ContainerServNotFoundException;

interface iContainer
{
    /**
     * Retrieve a registered service
     *
     * !! create service of first retrieve and store it.
     *    if service not exists self::fresh will call. 
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws ContainerCreateServiceException|ContainerServNotFoundException
     * @return mixed
     */
    function get($serviceName, $invOpt = array());

    /**
     * Retrieve a fresh instance of service
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws ContainerCreateServiceException|ContainerServNotFoundException
     * @return mixed
     */
    function fresh($serviceName, $invOpt = array());

    /**
     * Check for a registered instance
     *
     * @param string $serviceName Service Name
     *
     * @return boolean
     */
    function has($serviceName);
}
