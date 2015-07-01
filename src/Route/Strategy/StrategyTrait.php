<?php

namespace WP\Route\Strategy;

trait StrategyTrait
{
    /**
     * @var \WP\Route\Strategy\StrategyInterface
     */
    protected $strategy;

    /**
     * Tells the implementor which strategy to use, this should override any higher
     * level setting of strategies, such as on specific routes
     *
     * @param  \WP\Route\Strategy\StrategyInterface $strategy
     * @return void
     */
    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Gets global strategy
     *
     * @return integer
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
