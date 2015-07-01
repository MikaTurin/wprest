<?php
namespace WP\Route;

use Closure;
use FastRoute\Dispatcher as FastRoute;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use WP\Route\Http\Exception\MethodNotAllowedException;
use WP\Route\Http\Exception\NotFoundException;
use WP\Route\Strategy\RestfulStrategy;
use WP\Route\Strategy\StrategyInterface;
use WP\Route\Strategy\StrategyTrait;
use WP\InjectorTrait;
use RuntimeException;

class Dispatcher extends GroupCountBasedDispatcher
{
    use InjectorTrait;
    use StrategyTrait;

    /**
     * @var array
     */
    protected $routes;

    /**
     * Constructor
     *
     * @param array                                $routes
     * @param array                                $data
     */
    public function __construct(array $routes, array $data)
    {
        $this->routes    = $routes;
        parent::__construct($data);
    }

    /**
     * Match and dispatch a route matching the given http method and uri
     *
     * @param  string $method
     * @param  string $uri
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dispatch($method, $uri)
    {
        $match = parent::dispatch($method, $uri);
        
        if ($match[0] === FastRoute::NOT_FOUND) {
            return $this->handleNotFound();
        }
        
        if ($match[0] === FastRoute::METHOD_NOT_ALLOWED) {
            $allowed  = (array) $match[1];
            return $this->handleNotAllowed($allowed);
        }

        $handler  = (isset($this->routes[$match[1]]['callback'])) ? $this->routes[$match[1]]['callback'] : $match[1];        
        $strategy = $this->routes[$match[1]]['strategy'];
        $vars     = (array) $match[2];

        return $this->handleFound($handler, $strategy, $vars);
    }

    /**
     * Handle dispatching of a found route
     *
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @param  array                                    $vars
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \RuntimeException
     */
    protected function handleFound($handler, StrategyInterface $strategy = null, array $vars)
    {
        if (is_null($this->getStrategy())) {
            $this->setStrategy($strategy);
        }

        $controller = null;

        // figure out what the controller is
        if (($handler instanceof Closure) || (is_string($handler) && is_callable($handler))) {
            $controller = $handler;
        }

        if (is_string($handler) && strpos($handler, '::') !== false) {
            $controller = explode('::', $handler);
        }

        // if controller method wasn't specified, throw exception.
        if (! $controller) {
            throw new RuntimeException('A class method must be provided as a controller. ClassName::methodName');
        }

        return $strategy->dispatch($controller, $vars);
    }

    /**
     * Handle a not found route
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleNotFound()
    {
        $exception = new NotFoundException;

        if ($this->getStrategy() instanceof RestfulStrategy) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }

    /**
     * Handles a not allowed route
     *
     * @param  array $allowed
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleNotAllowed(array $allowed)
    {
        $exception = new MethodNotAllowedException($allowed);

        if ($this->getStrategy() instanceof RestfulStrategy) {
            return $exception->getJsonResponse();
        }

        throw $exception;
    }
}
