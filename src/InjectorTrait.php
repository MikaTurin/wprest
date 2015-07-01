<?php
namespace WP;

use Auryn\Provider;

trait InjectorTrait
{
    /** @var  \Auryn\Provider */
    protected $injector;

    public function getInjector()
    {
        if (!isset($this->injector)) {
            throw new \Exception('no injector instance');
        }

        return $this->injector;
    }

    public function setInjector(Provider $injector)
    {
        $this->injector = $injector;
    }
}