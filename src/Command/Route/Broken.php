<?php namespace WP\Command\Route;


class Broken extends All
{
    protected function configure()
    {
        $this
            ->setName('routes:broken')
            ->setDescription('List broken routes.')
        ;
    }

    protected function isActiveHandler($handler)
    {
        $call = explode('::', $handler);

        if (! class_exists($call[0])) return true;

        $cntrl = $this->app->getInjector()->make($call[0]);
        if (! method_exists($cntrl, $call[1])) return true;

        return false;
    }
}
