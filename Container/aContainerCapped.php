<?php
namespace Poirot\Ioc\Container;

use Poirot\Ioc\Container;
use Poirot\Ioc\Container\Exception\exContainerInvalidServiceType;


/**
 * Container that extends Capped Container
 * can have just validated services inside
 * this validated can achieve the services
 * from exact type(s).
 *
 * e.g View Renderer just have iRenderer
 * implementation as registered service.
 */
abstract class aContainerCapped
    extends Container
{
    /**
     * Validate Plugin Instance Object
     *
     * @param mixed $pluginInstance
     *
     * @throws exContainerInvalidServiceType
     * @return void
     */
    abstract function validateService($pluginInstance);

    /**
     * Retrieve a registered instance
     *
     * @param string $serviceName Service name
     * @param array  $invOpt      Invoke Options
     *
     * @throws exContainerInvalidServiceType
     * @return mixed
     */
    function get($serviceName, $invOpt = array())
    {
        $return = parent::get($serviceName, $invOpt);
        if (strpos($serviceName, self::SEPARATOR) === false)
            // validate just services on same namespace
            $this->validateService($return);

        return $return;
    }

    /**
     * Retrieve a fresh instance of service
     *
     * @param string $serviceName Service name
     * @param array $invOpt Invoke Options
     *
     * @throws \Exception
     * @return mixed
     */
    function fresh($serviceName, $invOpt = array())
    {
        $return = parent::fresh($serviceName, $invOpt);
        if (strpos($serviceName, self::SEPARATOR) === false)
            // validate just services on same namespace
            $this->validateService($return);

        return $return;
    }
    
    /**
     * @override
     *
     * Nest A Copy Of Container Within This Container
     *
     * @param Container   $container
     * @param string|null $namespace Container Namespace
     *
     * @return $this
     */
    function nest(Container $container, $namespace = null)
    {
        if (!$container instanceof $this)
            throw new \InvalidArgumentException(sprintf(
                'Only can nest with same type pluginManager object, given "%s".'
                , get_class($container)
            ));

        return parent::nest($container, $namespace);
    }
}
