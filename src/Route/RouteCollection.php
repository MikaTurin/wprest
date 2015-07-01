<?php

namespace WP\Route;

use Closure;
use FastRoute\DataGenerator;
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdRouteParser;
use WP\Route\Strategy\StrategyTrait;

class RouteCollection extends RouteCollector
{
    use StrategyTrait;

    /** @var array */
    protected $routingTable = [];

    /** @var array */
    protected $routes = [];

    protected $patternMatchers = [
        '/{(.+?):int}/'        => '{$1:[0-9]+}',
        '/{(.+?):word}/'          => '{$1:[a-zA-Z]+}',
        '/{(.+?):alphanum_dash}/' => '{$1:[a-zA-Z0-9-_]+}'
    ];

    /**
     * Constructor
     *
     * @param \FastRoute\RouteParser               $parser
     * @param \FastRoute\DataGenerator             $generator
     */
    public function __construct(
        RouteParser        $parser    = null,
        DataGenerator      $generator = null
    ) {
        // build parent route collector
        $parser    = ($parser instanceof RouteParser) ? $parser : new StdRouteParser;
        $generator = ($generator instanceof DataGenerator) ? $generator : new GroupCountBasedDataGenerator;
        parent::__construct($parser, $generator);
    }

    /**
     * Add a route to the collection
     *
     * @param  string                                   $method
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function addRoute($method, $route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        // if the handler is an anonymous function, we need to store it for later use
        // by the dispatcher, otherwise we just throw the handler string at FastRoute
        if ($handler instanceof Closure) {
            $callback = $handler;
            $handler  = uniqid('league::route::', true);

            $this->routes[$handler]['callback'] = $callback;
        }

        $this->routes[$handler]['strategy'] = (is_null($strategy)) ? $this->strategy : $strategy;


        $route = $this->parseRouteString($route);

        parent::addRoute($method, $route, $handler);

        $this->routingTable[] = [
            'method'    => $method,
            'route'     => $route,
            'handler'   => $handler
        ];

        return $this;
    }

    /**
     * Builds a dispatcher based on the routes attached to this collection
     *
     * @return \WP\Route\Dispatcher
     */
    public function getDispatcher()
    {
        $dispatcher = new Dispatcher($this->routes, $this->getData());

        if (! is_null($this->strategy)) {
            $dispatcher->setStrategy($this->strategy);
        }

        return $dispatcher;
    }

    /**
     * Add a route that responds to GET HTTP method
     *
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function get($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        return $this->addRoute('GET', $route, $handler, $strategy);
    }

    /**
     * Add a route that responds to POST HTTP method
     *
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function post($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        return $this->addRoute('POST', $route, $handler, $strategy);
    }

    /**
     * Add a route that responds to PUT HTTP method
     *
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function put($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        return $this->addRoute('PUT', $route, $handler, $strategy);
    }

    /**
     * Add a route that responds to PATCH HTTP method
     *
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function patch($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        return $this->addRoute('PATCH', $route, $handler, $strategy);
    }

    /**
     * Add a route that responds to DELETE HTTP method
     *
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function delete($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        return $this->addRoute('DELETE', $route, $handler, $strategy);
    }

    /**
     * Add a route that responds to HEAD HTTP method
     *
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function head($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        return $this->addRoute('HEAD', $route, $handler, $strategy);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method
     *
     * @param  string                                   $route
     * @param  string|\Closure                          $handler
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return \WP\Route\RouteCollection
     */
    public function options($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        return $this->addRoute('OPTIONS', $route, $handler, $strategy);
    }

    public function rest($route, $handler, Strategy\StrategyInterface $strategy = null)
    {
        $sep = '/';

        $route = rtrim($route, '/');
        if ($route == '') {
            $route = '/';
            $sep = '';
        }

        $this->addRoute('GET',      $route,                     $handler . '::index',   $strategy);
        $this->addRoute('GET',      $route . $sep . '{id:int}', $handler . '::show',    $strategy);
        $this->addRoute('POST',     $route,                     $handler . '::create',  $strategy);
        $this->addRoute('PUT',      $route . $sep . '{id:int}', $handler . '::edit',    $strategy);
        $this->addRoute('DELETE',   $route . $sep . '{id:int}', $handler . '::delete',  $strategy);
    }

    /**
     * Add a convenient pattern matcher to the internal array for use with all routes.
     *
     * @param string $keyWord
     * @param string $regex
     */
    public function addPatternMatcher($keyWord, $regex)
    {
        // Since the user is passing in a human-readable word, we convert that to the appropriate regex
        $pattern = '/{(.+?):' . $keyWord . '}/';
        $regex = '{$1:' . $regex . '}';

        $this->patternMatchers[$pattern] = $regex;
    }

    /**
     * Convenience method to convert pre-defined key words in to regex strings
     *
     * @param  string $route
     * @return string
     */
    protected function parseRouteString($route)
    {
        return preg_replace(array_keys($this->patternMatchers), array_values($this->patternMatchers), $route);
    }

    public function getRoutingTable()
    {
        return $this->routingTable;
    }
}