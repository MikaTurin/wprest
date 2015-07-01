<?php
namespace WP;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Monolog\Logger;
use Stack\Builder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class Application implements HttpKernelInterface, TerminableInterface
{
    use InjectorTrait;

    /** @var \WP\Route\RouteCollection */
    protected $router;

    /** @var \callable */
    protected $exceptionDecorator;

    /** @var array */
    protected $config = [];
    
    /** @var array */
    protected $loggers = [];

    /**
     * @param bool $debug Enable debug mode
     */
    public function __construct($debug = true)
    {
        $this->setConfig('debug', $debug);

        $this->setExceptionDecorator(function (\Exception $e) {
            $response = new Response;
            $response->setStatusCode(method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
            $response->headers->add(['Content-Type' => 'application/json']);

            $return = [
                'error' => [
                    'message' => $e->getMessage()
                ]
            ];

            if ($this->getConfig('debug', true) === true) {
                $return['error']['trace'] = explode(PHP_EOL, $e->getTraceAsString());
            }

            //$response->setContent(json_encode($return));
            $response->setContent('<pre>'.print_r($return, true).'</pre>');

            return $response;
        });
    }

    /**
     * Return the router.
     *
     * @return \WP\Route\RouteCollection
     */
    public function getRouter()
    {
        if (!isset($this->router)) {
            $this->router = $this->getInjector()->make('\WP\Route\RouteCollection');
            $this->getInjector()->share($this->router);
        }

        return $this->router;
    }

    /*
     * Return the event emitter.
     *
     * @return \League\Event\Emitter
     */
    //public function getEventEmitter()
    //{
    //    return $this->getEmitter();
    //}
    
    /**
     * Return a logger
     *
     * @param string $name
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger($name = 'default')
    {
        if (isset($this->loggers[$name])) {
            return $this->loggers[$name];
        }

        $logger = new Logger($name);
        $this->loggers[$name] = $logger;
        return $logger;
    }

    /**
     * Set the exception decorator.
     *
     * @param callable $func
     *
     * @return void
     */
    public function setExceptionDecorator(callable $func)
    {
        $this->exceptionDecorator = $func;
    }

    /**
     * Handle the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int                                       $type
     * @param bool                                      $catch
     *
     * @throws \Exception
     * @throws \LogicException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $this->getInjector()->share($request);

        try {

            //$this->emit('request.received', $request);

            $dispatcher = $this->getRouter()->getDispatcher();
            $response = $dispatcher->dispatch(
                $request->getMethod(),
                $request->getPathInfo()
            );

            //$this->emit('response.created', $request, $response);

            return $response;

        } catch (\Exception $e) {

            if (!$catch) {
                throw $e;
            }

            $response = call_user_func($this->exceptionDecorator, $e);
            if (!$response instanceof Response) {
                throw new \LogicException('Exception decorator did not return an instance of Symfony\Component\HttpFoundation\Response');
            }

            //$this->emit('response.created', $request, $response);

            return $response;
        }
    }

    /**
     * Terminates a request/response cycle.
     *
     * @param \Symfony\Component\HttpFoundation\Request  $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        //$this->emit('response.sent', $request, $response);
    }

    /**
     * Run the application.
     *
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     *
     * @return void
     */
    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $app = (new Builder())
            ->push('WP\Middleware\ApiVersion')
            ->resolve($this);

        $response = $app->handle($request, self::MASTER_REQUEST, false);
        $response->send();

        $this->terminate($request, $response);
    }

    /*
     * Subscribe to an event.
     *
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     */
    //public function subscribe($event, $listener, $priority = ListenerAcceptorInterface::P_NORMAL)
    //{
    //    $this->addListener($event, $listener, $priority);
    //}

    /**
     * Set a config item
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * Get a config key's value
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
}
