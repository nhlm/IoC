<?php
namespace Poirot\Ioc\Container\Service;

/*
 * $container->set(new FunctorService([
 *       'name'     => 'serviceName',
 *       'callable' => function($arg1, $arg2) {
 *           # callable function will bind to service object as closure method
 *           # so you can access methods from FunctorService
 *           $sc = $this->getServiceContainer();
 *
 *           # here we return service result
 *           return $arg1.' '.$arg2;
 *       },
 *       'allow_override'   => false
 * ]));
 *
 * $container->get('serviceName', [$arg1Val, $arg2Val]);
 *
 * ...........................................................................
 * 'callable' => function($arg1, $arg2)  <---->  get('name', [12, 4])
 * 'callable' => function($arg1)         <---->  get('name', 'hello')
 *                                       <---->  get('name', ['hello'])
 * using arguments resolver:
 * @see ANamedResolver may change
 * 'callable' => function($arg1, int $x) <---->  get('name', [4, 'arg1' => '12'])
 *
 */

class ServiceFactory 
    extends aServiceContainer
{
    /**
     * Function Arguments as a Container::get arg. options
     *
     * @var array
     * @see Container::initializer
     * @see Container::get
     */
    public $invoke_options;

    /** @var callable */
    protected $callable;


    /**
     * Construct
     *
     * also can used as:
     * - new FunctorService('name', function() {});
     * - new FunctorService('name', ['callable' => .. ..options]);
     * or setter set
     * - new FunctorService([ 'callable' => [..] ..options])
     *
     * @param array|callable $nameOrSetter
     * @param null|string    $setter
     */
    function __construct($nameOrSetter = array(), $setter = null)
    {
        if (is_string($nameOrSetter) && is_callable($setter)) {
            ## new FunctorService('name', function() {})
            $setter = array('callable' => $setter);
        }
        
        parent::__construct($nameOrSetter, $setter);
    }

    /**
     * Set createService Delegate
     *
     * - it will bind to service object as closure method
     *   so, you can access to methods from FunctorService
     *   from function() { $this->getServiceContainer() }
     *
     * @param callable $callable
     *
     * @return $this
     */
    function setCallable(/*callable*/$callable)
    {
        if (!is_callable($callable))
            throw new \InvalidArgumentException(sprintf(
                'Given argument must be a callable. given: %s'
                , \Poirot\Std\flatten($callable)
            ));
        
        $this->callable = $callable;
        return $this;
    }

    /**
     * Create Service
     *
     * @return mixed
     */
    function newService()
    {
        $callable = $this->callable;
        if ($callable === null)
            ## no callable provided but rather not throw exception
            return;
        
        // ..
        // DO_LEAST_PHPVER_SUPPORT 5.4 Closure Bind
        if ($callable instanceof \Closure && version_compare(phpversion(), '5.4.0') > 0)
            $callable = $callable->bindTo($this);

        // ...
        $arguments = $this->optsData();
        $callable  = \Poirot\Std\Invokable\resolveCallableWithArgs($callable, $arguments);
        return call_user_func($callable);
    }
}
