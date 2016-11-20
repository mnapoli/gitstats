<?php
declare(strict_types = 1);

namespace GitIterator;

use GitIterator\Formatter\Formatter;
use GitIterator\Helper\CommandRunner;
use GitIterator\Helper\Git;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class TaskRunner
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

    public function __invoke(string $format = null, ConsoleOutputInterface $output)
    {
        $stderr = $output->getErrorOutput();
        $repositoryDirectory = __DIR__ . '/../repository';

        // Load configuration
        if (!file_exists('conf.yml')) {
            throw new \Exception('Configuration file "conf.yml" missing');
        }
        $configuration = Yaml::parse(file_get_contents('conf.yml'));
        $repositoryUrl = $configuration['repository'];

        // Check the existing directory
        if (is_dir($repositoryDirectory)) {
            $url = $this->git->getRemoteUrl($repositoryDirectory);
            if ($url !== $repositoryUrl) {
                $stderr->writeln('Existing directory "repository" found, removing it');
                $this->filesystem->remove($repositoryDirectory);
            }
        }

        // Clone the repository
        if (!is_dir($repositoryDirectory)) {
            $stderr->writeln(sprintf('Cloning %s in directory "repository"', $repositoryUrl));
            $this->git->clone($repositoryUrl, $repositoryDirectory);
        }

        // Get the list of commits
        $commits = $this->git->getCommitList($repositoryDirectory, 'master');
        $stderr->writeln(sprintf('Iterating through %d commits', count($commits)));

        $data = $this->process($commits, $repositoryDirectory, $configuration['tasks']);

        $format = $format ?: 'csv';
        $formatterClass = sprintf('GitIterator\Formatter\%sFormatter', ucfirst($format));
        /** @var Formatter $formatter */
        $formatter = new $formatterClass;
        $data = $formatter->format($configuration['tasks'], $data);
        foreach ($data as $line) {
            $output->writeln($line);
        }

        $stderr->writeln('Done');
    }

    private function process($commits, $directory, array $tasks) : \Generator
    {
        foreach ($commits as $commit) {
            $this->git->checkoutCommit($directory, $commit);
            $timestamp = $this->git->getCommitTimestamp($directory, $commit);
            $data = [
                'commit' => $commit,
                'date' => date('Y-m-d H:i:s', $timestamp),
            ];
            foreach ($tasks as $taskName => $taskCommand) {
                $taskResult = $this->commandRunner->runInDirectory($directory, $taskCommand);
                $data[$taskName] = $taskResult;
            }
            yield $data;
        }
    }
}
