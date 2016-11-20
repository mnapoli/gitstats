<?php
declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

use GitIterator\Git;
use GitIterator\RunCommand;
use Silly\Edition\PhpDi\Application;
use Symfony\Component\Filesystem\Filesystem;

$app = new Application();

$app->command('run [--format=]', RunCommand::class);

$app->command('clear', function (Filesystem $filesystem, Git $git) {
    $repositoryDirectory = __DIR__ . '/repository';
    $gitLock = $repositoryDirectory . '/.git/HEAD.lock';
    if ($filesystem->exists($gitLock)) {
        $filesystem->remove($gitLock);
    }
    $git->reset($repositoryDirectory, 'master', true);
});

$app->run();
