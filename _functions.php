<?php
namespace
{
    use Poirot\Ioc\Container;

    /**
     * Class IOC
     *
     * Helper To Ease Access To Ioc Services by extend this:
     *   - on static call use extend class namespace to achieve nested container
     *     and retrieve service from that container.
     *
     *   $directory = \Module\Categories\Services\Repository\IOC::categories();
     *   $r = $directory->getTree($directory->findByID('red'));
     *
     *   equal to:
     *
     *   $ioc->from('/Module/Categories/Services/Repository')->get('categories');
     *
     */
    class IOC
    {
        /** @var Container */
        protected static $_IOC;

        static function __callStatic($name, $arguments)
        {
            $class     = get_class(new static);
            $namespace = substr($class, 0, strrpos($class, '\\'));
            $nested    = str_replace('\\', Container::SEPARATOR, $namespace);
            $container = self::GetIoC()->from($nested);

            if (!$container)
                throw new \Exception(sprintf('Nested Container (%s) not included.', $nested));

            if ($arguments)
                $service = $container->get($name, $arguments);
            else
                $service = $container->get($name);

            return $service;
        }

        /**
         * Retrieve IOC Instance
         *
         * @return Container
         */
        static function GetIoC()
        {
            if (!self::$_IOC)
                self::GiveIoC(new Container);

            return self::$_IOC;
        }

        /**
         * Set IOC Instance (Immutable)
         *
         * @param Container $container
         */
        static function GiveIoC(Container $container)
        {
            if (self::$_IOC)
                throw new \RuntimeException('IoC Container is Immutable and Given before.');

            self::$_IOC = $container;
        }
    }
}

namespace Poirot\Ioc
{
    const INIT_INS = '_class_';

    /**
     * Instantiate Initialized From Config Data.
     *
     * if services(ioc) not given using default Poirot\ioc() then:
     *   - make object instance from definition data structure
     *   - inject dependencies
     *   - initialize services
     *
     * @param array|\Traversable $config
     * @param null|Container     $services
     *
     * @return array Config replaced with initialized services
     * @throws \Exception
     */
    function newInitIns($config, Container $services = null)
    {
        /*
        'identifier' => array(
            // [X] This will convert into Identifier instance [ 'identifier' => ObjectInstance ]
            \Poirot\Config\INIT_INS   => [
                '\Poirot\AuthSystem\Authenticate\Identifier\IdentifierHttpBasicAuth',
                'options' => array(
                    #O# adapter => iIdentityCredentialRepo | (array) options of CredentialRepo
                    'credential_adapter' => array(
                        // [X] This will convert into instance [ 'credential_adapter' => ObjectInstance ]
                        \Poirot\Config\INIT_INS   => [
                            '\Poirot\AuthSystem\Authenticate\RepoIdentityCredential\IdentityCredentialDigestFile',
                            'options' => array(
                                'pwd_file_path' => __DIR__.'/../data/users.pws',
                            ),
                        ],
                    )
                ),
            ],
        ),
        */

        if ($config instanceof \Traversable)
            $config = \Poirot\Std\cast($config)->toArray();

        if (!is_array($config))
            throw new \InvalidArgumentException(sprintf(
                'Config must be Array Or Traversable; given: (%s).'
                , \Poirot\Std\flatten($config)
            ));

        if ($services === null)
            // using default container to initialize instances
            $services = \IOC::GetIoC();

        if (!$services instanceof Container)
            throw new \InvalidArgumentException(sprintf(
                'Services must instance of Container; given: (%s).'
                , \Poirot\Std\flatten($services)
            ));


        $services = clone $services;

        foreach ($config as $key => $value)
        {
            if ($key === INIT_INS)
            {
                // instance object from _class_ config definition
                // 'key' => [ \Poirot\Config\INIT_INS => '\ClassName' | ['\ClassName', 'options' => $options] ]
                if (is_array($value))
                    // Maybe Options Contains Initialized Definition
                    $value = newInitIns($value, $services);
                elseif (is_string($value))
                    $value = array($value);
                else
                    throw new \Exception(sprintf(
                        'Invalid instanceInitialized Config (%s).', \Poirot\Std\flatten($value)
                    ));

                $class        = array_shift($value);
                // easy to debug track of service if failed; replace separator container "/" with "_" to avoid container service retrieve error
                $postfix      = '_'.str_replace('/', '_', \Poirot\Std\flatten($class));
                $service_name = uniqid().$postfix;
                $inService    = new Container\Service\ServiceInstance();
                $inService->setName($service_name);
                $inService->setService($class);
                $inService->with($value);

                $services->set($inService);
                $initialized = $services->get($service_name);
                unset($config[$key]);
                if (empty($config))
                    // only definition structure and will convert to instance only
                    $config = $initialized;
                else
                    array_unshift($config, $initialized);
            }
            elseif (is_array($value))
            {
                $config[$key] = newInitIns($value, $services);
            }
        }

        return $config;
    }
}
