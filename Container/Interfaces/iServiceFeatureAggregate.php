<?php
namespace Poirot\Ioc\Container\Interfaces;

/**
 * If we try to retrieve service that not register in container
 * in regular service way(service with specific given name), 
 * container iterate over Aggregate Feature Services and check
 * :canCreate then 
 */
interface iServiceFeatureAggregate
    extends iContainerService
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
     * note: when with service called ::newService() will return
     *       mentioned service only and current state can only change with another
     *       call to this method and request for another service
     *
     * @param string $serviceName
     * 
     * @return iContainerService
     */
    function withServiceName($serviceName = null);
}
