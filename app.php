<?php
declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

use GitIterator\RunCommand;
use Silly\Edition\PhpDi\Application;

$app = new Application();

$app->command('run [--graphite]', RunCommand::class);

$app->run();
