<?php
namespace Poirot\Container\Interfaces\Respec;

use Poirot\Container\Interfaces\iContainer;

interface iServicesProvider
{
    /**
     * Services Container
     *
     * @return iContainer
     */
    function services();
}
