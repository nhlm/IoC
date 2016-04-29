<?php
namespace Poirot\Container\Interfaces;

interface iBuilderContainer
{
    /**
     * Configure container manager
     *
     * @param iContainer $container
     */
    function build(/*iContainer*/ $container);
}
