<?php
namespace Poirot\Ioc\Container\Service;

use Poirot\Ioc\Container\Interfaces\iContainerService;
use Poirot\Ioc\Container\Interfaces\iServiceFeatureAggregate;

abstract class aServiceAggregate
    extends aServiceContainer
    implements iServiceFeatureAggregate
{
    /** @var string Requested Service */
    protected $currentService;
    
    
    /**
     * Determine Which Can Create Service With Given Name?
     *
     * @param string $serviceName
     *
     * @return boolean
     */
    abstract function canCreate($serviceName);

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
     * @throws \Exception
     */
    final function withServiceName($serviceName = null)
    {
        if (false == $this->canCreate($serviceName))
            throw new \Exception(sprintf(
                'Can`t Create (%s).'
                , \Poirot\Std\flatten($serviceName)
            ));

        $new = clone $this;
        $new->currentService = $serviceName;
        return $new;
    }
}