<?php namespace WP\Command\Route;


class Working extends All
{
    protected function configure()
    {
        $this
            ->setName('routes:working')
            ->setDescription('List working routes.')
        ;
    }

    protected function isActiveHandler($handler)
    {
        $call = explode('::', $handler);

        if (class_exists($call[0])) {
            $cntrl = $this->app->getInjector()->make($call[0]);
            if (method_exists($cntrl, $call[1])) return true;
        }

        return false;
    }
}
