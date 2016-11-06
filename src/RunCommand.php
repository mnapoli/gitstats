<?php
declare(strict_types = 1);

namespace GitIterator;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class RunCommand
{
    /**
     * @var Git
     */
    private $git;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var CommandRunner
     */
    private $commandRunner;

    public function __construct(Git $git, Filesystem $filesystem, CommandRunner $commandRunner)
    {
        $this->git = $git;
        $this->filesystem = $filesystem;
        $this->commandRunner = $commandRunner;
    }

    public function __invoke(bool $graphite = null, OutputInterface $output)
    {
        $graphite = $graphite ?: false;
        $repositoryDirectory = __DIR__ . '/../repository';

        // Load configuration
        if (!file_exists('conf.yml')) {
            throw new \Exception('Configuration file "conf.yml" missing');
        }
        $configuration = Yaml::parse(file_get_contents('conf.yml'));

        // Check the existing directory
        if (is_dir($repositoryDirectory)) {
            $url = $this->git->getRemoteUrl($repositoryDirectory);
            if ($url !== $configuration['repository']) {
                $this->outputInfo('<comment>Existing directory "repository" found, removing it</comment>', $graphite, $output);
                $this->filesystem->remove($repositoryDirectory);
            }
        }

        // Clone the repository
        if (!is_dir($repositoryDirectory)) {
            $this->outputInfo(sprintf('<comment>Cloning %s in directory "repository"</comment>', $configuration['repository']), $graphite, $output);
            $this->git->clone($configuration['repository'], $repositoryDirectory);
        }

        $commits = $this->git->getCommitList($repositoryDirectory, 'master');
        $this->outputInfo(sprintf('<comment>Iterating through %d commits</comment>', count($commits)), $graphite, $output);

        foreach ($commits as $commit) {
            $this->git->checkoutCommit($repositoryDirectory, $commit);
            $timestamp = $this->git->getCommitTimestamp($repositoryDirectory, $commit);
            foreach ($configuration['tasks'] as $taskId => $taskCommand) {
                $result = $this->commandRunner->runInDirectory($repositoryDirectory, $taskCommand);
                $this->outputInfo(sprintf(
                    '<info>%s: %s</info> on commit <info>%s</info> (%s)',
                    $taskId,
                    $result,
                    $commit,
                    date('j M Y', $timestamp)
                ), $graphite, $output);
                if ($graphite) {
                    $output->writeln("$taskId $result $timestamp");
                }
            }
        }
    }

    private function outputInfo(string $message, bool $graphite, OutputInterface $output)
    {
        if ($graphite) {
            return;
        }
        $output->writeln($message);
    }
}
