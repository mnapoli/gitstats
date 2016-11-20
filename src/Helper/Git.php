<?php
declare(strict_types = 1);

namespace GitIterator\Helper;

/**
 * Wrapper around git commands.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Git
{
    /**
     * @var CommandRunner
     */
    private $commandRunner;

    public function __construct(CommandRunner $commandRunner)
    {
        $this->commandRunner = $commandRunner;
    }

    public function clone($url, $directory)
    {
        $this->commandRunner->run("git clone $url \"$directory\"");
    }

    public function checkoutOriginBranch(string $directory, string $branch)
    {
        $this->run($directory, "git checkout -b $branch origin/$branch");
    }

    public function getRemoteUrl(string $directory, string $remote = 'origin') : string
    {
        return trim($this->run($directory, "git config --get remote.$remote.url"));
    }

    /**
     * Returns the list of commits in the given branch, going backwards.
     * @return string[]
     */
    public function getCommitList(string $directory, string $branch = 'master') : array
    {
        $output = $this->run($directory, "git rev-list $branch");
        $commits = explode(PHP_EOL, $output);
        $commits = array_filter($commits);
        return $commits;
    }

    public function checkoutCommit(string $directory, string $commit)
    {
        $this->run($directory, "git checkout $commit");
    }

    public function reset(string $directory, string $commit, bool $hard)
    {
        $mode = $hard ? '--hard' : '';
        $this->run($directory, "git reset $mode $commit");
    }

    public function getCommitTimestamp(string $directory, string $commit) : int
    {
        return (int) $this->run($directory, "git show -s --format=%ct $commit");
    }

    private function run(string $directory, string $command) : string
    {
        return $this->commandRunner->runInDirectory($directory, $command);
    }
}
