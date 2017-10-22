<?php
declare(strict_types=1);

namespace GitStats;

use DI\Container;
use DI\ContainerBuilder;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Application extends \Silly\Application
{
    public function __construct()
    {
        parent::__construct('gitstats', 'UNKNOWN');

        $this->useContainer($this->createContainer(), true, true);
    }

    protected function createContainer() : Container
    {
        $builder = new ContainerBuilder;
        $builder->addDefinitions([
            Application::class => $this,
        ]);

        return ContainerBuilder::buildDevContainer();
    }
}
