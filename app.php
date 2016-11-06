<?php
declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

use GitIterator\CommandRunner;
use GitIterator\Git;
use Silly\Edition\PhpDi\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

$app = new Application();

$app->command('run [--graphite]', function (
    bool $graphite = null,
    OutputInterface $output,
    Git $git,
    Filesystem $filesystem,
    CommandRunner $commandRunner
) {
    $repositoryDirectory = __DIR__ . '/repository';

    // Load configuration
    if (!file_exists('conf.yml')) {
        throw new Exception('Configuration file "conf.yml" missing');
    }
    $configuration = Yaml::parse(file_get_contents('conf.yml'));

    // Check the existing directory
    if (is_dir($repositoryDirectory)) {
        $url = $git->getRemoteUrl($repositoryDirectory);
        if ($url !== $configuration['repository']) {
            $output->writeln('<comment>Existing directory "repository" found, removing it</comment>');
            $filesystem->remove($repositoryDirectory);
        }
    }

    // Clone the repository
    if (!is_dir($repositoryDirectory)) {
        $output->writeln(sprintf('<comment>Cloning %s in directory "repository"</comment>', $configuration['repository']));
        $git->clone($configuration['repository'], $repositoryDirectory);
    }

    $commits = $git->getCommitList($repositoryDirectory, 'master');
    $output->writeln(sprintf('<comment>Iterating through %d commits</comment>', count($commits)));

    foreach ($commits as $commit) {
        $git->checkoutCommit($repositoryDirectory, $commit);
        $timestamp = $git->getCommitTimestamp($repositoryDirectory, $commit);
        foreach ($configuration['tasks'] as $taskId => $taskCommand) {
            $result = $commandRunner->runInDirectory($repositoryDirectory, $taskCommand);
            $output->writeln(sprintf(
                '<info>%s: %s</info> on commit <info>%s</info> (%s)',
                $taskId,
                $result,
                $commit,
                date('j M Y', $timestamp)
            ));
        }
    }
});

$app->run();
