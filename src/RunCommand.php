<?php
declare(strict_types = 1);

namespace GitIterator;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
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

    public function __invoke(ConsoleOutputInterface $output)
    {
        $stderr = $output->getErrorOutput();
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
                $stderr->writeln('Existing directory "repository" found, removing it');
                $this->filesystem->remove($repositoryDirectory);
            }
        }

        // Clone the repository
        if (!is_dir($repositoryDirectory)) {
            $stderr->writeln(sprintf('Cloning %s in directory "repository"', $configuration['repository']));
            $this->git->clone($configuration['repository'], $repositoryDirectory);
        }

        // Get the list of commits
        $commits = $this->git->getCommitList($repositoryDirectory, 'master');
        $stderr->writeln(sprintf('Iterating through %d commits', count($commits)));

        // Echo the column names
        $taskNames = array_keys($configuration['tasks']);
        array_unshift($taskNames, 'Commit', 'Date');
        $output->writeln(implode(',', $taskNames));

        foreach ($commits as $commit) {
            $this->git->checkoutCommit($repositoryDirectory, $commit);
            $timestamp = $this->git->getCommitTimestamp($repositoryDirectory, $commit);
            $line = [];
            foreach ($configuration['tasks'] as $taskName => $taskCommand) {
                $line[] = $this->commandRunner->runInDirectory($repositoryDirectory, $taskCommand);
            }
            array_unshift($line, $commit, date('Y-m-d H:i:s', $timestamp));
            $line = array_map(function (string $str) {
                return '"' . addslashes($str) . '"';
            }, $line);
            $output->writeln(implode(',', $line));
        }

        $stderr->writeln('Done');
    }
}
