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

    public function run(string $format = null, ConsoleOutputInterface $output)
    {
        $stderr = $output->getErrorOutput();
        $repositoryDirectory = __DIR__ . '/../repository';
        $configuration = $this->loadConfiguration();
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

        $data = $this->processCommits($commits, $repositoryDirectory, $configuration['tasks']);

        $this->formatAndOutput($format, $output, $configuration, $data);

        $stderr->writeln('Done');
    }

    public function runOnce(string $format = null, ConsoleOutputInterface $output)
    {
        $stderr = $output->getErrorOutput();
        $repositoryDirectory = __DIR__ . '/../repository';
        $configuration = $this->loadConfiguration();

        $commit = $this->git->getCurrentCommit($repositoryDirectory);

        $data = [$this->processDirectory($commit, $repositoryDirectory, $configuration['tasks'])];

        $this->formatAndOutput($format, $output, $configuration, $data);

        $stderr->writeln('Done');
    }

    private function processCommits($commits, $directory, array $tasks) : \Generator
    {
        foreach ($commits as $commit) {
            $this->git->checkoutCommit($directory, $commit);
            yield $this->processDirectory($commit, $directory, $tasks);
        }
    }

    private function processDirectory(string $commit, string $directory, array $tasks) : array
    {
        $timestamp = $this->git->getCommitTimestamp($directory, $commit);
        $data = [
            'commit' => $commit,
            'date' => date('Y-m-d H:i:s', $timestamp),
        ];
        foreach ($tasks as $taskName => $taskCommand) {
            $taskResult = $this->commandRunner->runInDirectory($directory, $taskCommand);
            $data[$taskName] = $taskResult;
        }
        return $data;
    }

    private function formatAndOutput(string $format, ConsoleOutputInterface $output, $configuration, $data)
    {
        $format = $format ?: 'csv';
        $formatterClass = sprintf('GitIterator\Formatter\%sFormatter', ucfirst($format));
        /** @var Formatter $formatter */
        $formatter = new $formatterClass;
        $data = $formatter->format($configuration['tasks'], $data);
        foreach ($data as $line) {
            $output->writeln($line);
        }
    }

    private function loadConfiguration() : array
    {
        if (! file_exists('gitstats.yml')) {
            throw new \Exception('Configuration file "gitstats.yml" missing');
        }
        return Yaml::parse(file_get_contents('gitstats.yml'));
    }
}
