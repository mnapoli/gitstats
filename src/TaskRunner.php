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

    public function run(string $directory = null, string $format = null, ConsoleOutputInterface $output)
    {
        $format = $format ?: 'csv';
        $directory = $directory ? realpath($directory) : getcwd();
        if (!is_dir($directory)) {
            throw new \Exception('Unknown directory ' . $directory);
        }
        $configuration = $this->loadConfiguration();

        // Get the list of commits
        $commits = $this->git->getCommitList($directory, 'master');
        $output->getErrorOutput()->writeln(sprintf('Iterating through %d commits', count($commits)));

        $data = $this->processCommits($commits, $directory, $configuration['tasks']);

        $this->formatAndOutput($format, $output, $configuration, $data);

        $output->getErrorOutput()->writeln('Done');
    }

    public function runOnce(string $directory = null, string $format = null, ConsoleOutputInterface $output)
    {
        $format = $format ?: 'csv';
        $directory = $directory ? realpath($directory) : getcwd();
        if (!is_dir($directory)) {
            throw new \Exception('Unknown directory ' . $directory);
        }
        $configuration = $this->loadConfiguration();

        $commit = $this->git->getCurrentCommit($directory);

        $data = [$this->processDirectory($commit, $directory, $configuration['tasks'])];

        $this->formatAndOutput($format, $output, $configuration, $data);

        $output->getErrorOutput()->writeln('Done');
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

    private function formatAndOutput(string $format, ConsoleOutputInterface $output, array $configuration, $data)
    {
        $format = $format ?: 'csv';
        $formatterClass = sprintf('GitIterator\Formatter\%sFormatter', ucfirst($format));
        /** @var Formatter $formatter */
        $formatter = new $formatterClass;
        $data = $formatter->format($configuration, $data);
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
