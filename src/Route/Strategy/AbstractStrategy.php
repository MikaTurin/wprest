<?php

namespace WP\Route\Strategy;

use Auryn\Provider;
use Symfony\Component\HttpFoundation\Response;
use WP\InjectorTrait;

abstract class AbstractStrategy
{
    use InjectorTrait;

    public function __construct(Provider $injector)
    {
        $this->setInjector($injector);
    }

    /**
     * @param array $controller
     * @param array $vars
     * @return mixed
     * @throws \ErrorException
     */
    protected function invokeController(array $controller, array $vars = [])
    {
        #TODO: proverki dobavitj, izbavitsja ot closure
        $class = $this->injector->make($controller[0]);
        $method = $controller[1];

        $args = array();
        array_walk($vars, function ($value, $key) use (&$args) {
            $args[sprintf(':%s', $key)] = $value;
        });

        if (method_exists($controller, 'init')) $this->injector->execute(array($controller, 'init'));
        return $this->injector->execute(array($class, $method), $args);
    }

    /**
     * Attempt to build a response
     *
     * @param  mixed $response
     * @return mixed
     */
    protected function determineResponse($response)
    {
        if ($response instanceof Response) {
            return $response;
        }

        try {
            $response = new Response($response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to build Response from controller return value', 0, $e);
        }

        return $response;
    }
}
