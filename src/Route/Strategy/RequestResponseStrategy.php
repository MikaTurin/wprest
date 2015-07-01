<?php
namespace WP\Route\Strategy;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RequestResponseStrategy extends AbstractStrategy implements StrategyInterface
{
    /** @var \Symfony\Component\HttpFoundation\Request */
    protected $request;

    /** @var /Symfony\Component\HttpFoundation\Response  */
    protected $response;

    /**
     * {@inheritdoc}
     */
    public function dispatch($controller, array $vars)
    {
        $response = $this->invokeController($controller, $vars);

        if ($response instanceof Response) {
            return $response;
        }

        throw new RuntimeException(
            'When using the Request -> Response Strategy your controller must ' .
            'return an instance of [Symfony\Component\HttpFoundation\Response]'
        );
    }
}
