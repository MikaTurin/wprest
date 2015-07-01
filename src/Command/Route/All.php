<?php namespace WP\Command\Route;

use WP\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class All extends Command
{
    /** @var \WP\Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('routes:all')
            ->setDescription('List all registered routes.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $routes = [];
        $routingTable = $this->app->getRouter()->getRoutingTable();

        usort($routingTable, function ($a, $b) {
            return ($a['route'] < $b['route']) ? -1 : 1;
        });

        foreach ($routingTable as $routingTableRecord) {
            if ($routingTableRecord['handler'] instanceof \Closure) {
                $routes[] = [
                    $routingTableRecord['method'],
                    $routingTableRecord['route'],
                    'Closure'
                ];
            } else {
                if (! $this->isActiveHandler($routingTableRecord['handler'])) continue;

                $routes[] = [
                    $routingTableRecord['method'],
                    $routingTableRecord['route'],
                    $routingTableRecord['handler']
                ];
            }
        }

        $table = $this->getHelper('table');
        $table
            ->setHeaders(array('Method', 'Route', 'Handler'))
            ->setRows($routes)
        ;
        $table->render($output);

        $output->writeln('Total: ' . sizeof($routes));
        $output->writeln('');
    }

    protected function isActiveHandler($handler)
    {
        return true;
    }
}
