<?php
namespace WP\Middleware;

use WP\Application;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;


class ApiVersion implements HttpKernelInterface
{
    /** @var Application */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param Request $request
     * @param int $type
     * @param bool $catch
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        /*$request->setApiVersion(
            $this->app->getContainer()->get(Application::CNTRID_API_VERSION)
        );*/

        return $this->app->handle($request, $type, $catch);
    }
}
