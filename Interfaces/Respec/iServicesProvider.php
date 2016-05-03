<?php
namespace Poirot\Ioc\Interfaces\Respec;

use Poirot\Ioc\Interfaces\iContainer;

interface iServicesProvider
{
    /**
     * Services Container
     *
     * @return iContainer
     */
    function services();
}
