<?php
namespace Poirot\Ioc\Interfaces;

use Poirot\Container\Exception\exContainerCreateService;
use Poirot\Container\Exception\exContainerNoService;

interface iContainer
{
    /**
     * Retrieve a registered service
     *
     * !! create service of first retrieve and store it.
     *    if service not exists self::fresh will call. 
     *
     * @param string $nameOrAlias Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws exContainerCreateService|exContainerNoService
     * @return mixed
     */
    function get($nameOrAlias, $invOpt = array());

    /**
     * Retrieve a fresh instance of service
     *
     * @param string $nameOrAlias Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws exContainerCreateService|exContainerNoService
     * @return mixed
     */
    function fresh($nameOrAlias, $invOpt = array());

    /**
     * Check for a registered instance
     *
     * @param string $nameOrAlias Service Name
     *
     * @return boolean
     */
    function has($nameOrAlias);
}
