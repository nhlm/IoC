<?php
namespace Poirot\Ioc\Container\Interfaces;

/**
 * If we try to retrieve service that not register in container
 * in regular service way(service with specific given name), 
 * container iterate over Aggregate Feature Services and check
 * :canCreate then 
 */
interface iServiceFeatureAggregate
{
    /**
     * Determine Which Can Create Service With Given Name?
     * 
     * @param string $serviceName
     * 
     * @return boolean
     */
    function canCreate($serviceName);

    /**
     * Set Create Service Method Respond To This Service
     * 
     * @param string $serviceName
     * 
     * @return $this
     */
    function by($serviceName);

    /**
     * Create Service
     *
     * @param string $serviceName
     * 
     * @return mixed
     */
    function newService($serviceName = null);

    /**
     * Get Last Created Service
     * 
     * @return mixed|null
     */
    function getLastCreatedService();
}
