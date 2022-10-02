<?php

/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2022, Ferry Cools (DigiLive)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

declare(strict_types=1);

namespace DigiLive\GitChangelog\RepoHandler;

use DigiLive\GitChangelog\GitChangelog;
use DigiLive\GitChangelog\Utilities\ArrayUtils;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Git repository handler.
 *
 * Class to address a git repository by running git cli commands.
 */
class RepoHandler
{
    /**
     * @var array Contains the processed information which is fetched from the git repository.
     */
    protected $commitData;
    /**
     * @var array Contains the user options used by the class.
     *
     * <pre>
     * branch               The branch to use.
     * fromTag              The oldest tag to fetch.
     *                      If the value is null it refers to the oldest commit.
     * toTag                The newest tag to fetch.
     *                      If the value is null, it refers to the HEAD revision of the checked-out branch.
     * includeMergeCommits  True includes merge commits in the title lists.
     * tagOrderBy           Specifies on which field the fetched tags have to be ordered.
     * </pre>
     *
     * @see https://git-scm.com/docs/git-for-each-ref
     */
    protected $options = [
        'fromTag'             => null,
        'toTag'               => null,
        'includeMergeCommits' => false,
        'tagOrderBy'          => 'creatordate',
    ];
    /**
     * @var string Path to local git repository.
     */
    private $repoPath;
    /**
     * @var array Contains the tags which exist in the git repository.
     *            If the first element's key is null, it refers to the HEAD revision of the checked-out branch.
     * @see GitChangelog::fetchTags();
     */
    private $gitTags;

    /**
     * @var string|null Path to the Git executable. If not found automatically, it assumed the path is included in the
     *                  systems' environment variables.
     */
    private $gitExecutable;

    /**
     * Constructor.
     *
     * All git tags are pre-fetched from the repository.
     * Defining a custom path to a repository, turns off autodiscovery of the repository directory.
     *
     * @param   ?string  $repoPath  Path to the repository directory.
     *
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
     */
    public function __construct(?string $repoPath = null)
    {
        $this->repoPath      = $repoPath;
        $executableFinder    = new ExecutableFinder();
        $this->gitExecutable = $executableFinder->find('git', 'git');

        $this->fetchTags(true);
    }

    /**
     * Fetch and cache tags from the git repository.
     *
     * @param   bool  $refresh  Set to true to refresh the cached tags.
     *
     * @return array The (re)cached tags.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the tags fails.
     */
    public function fetchTags(bool $refresh): array
    {
        $length = null;
        try {
            if ($refresh || null === $this->gitTags) {
                // Fetch all tags from the repository.
                $this->gitTags = [];

                // Cache the tags and add HEAD revision. $this->gitTags = [newest..oldest].
                $this->gitTags = $this->runGitCommand(['tag', '--sort', "-{$this->options['tagOrderBy']}"], true);
                array_unshift($this->gitTags, null);
            }

            // Return the cached tags.
            $toKey = ArrayUtils::arraySearch($this->options['toTag'], $this->gitTags);
            if (null !== $this->options['fromTag']) {
                $length = ArrayUtils::arraySearch($this->options['fromTag'], $this->gitTags) - $toKey + 1;
            }

            return array_slice($this->gitTags, $toKey, $length);
        } catch (\Throwable $e) {
            throw new RepoHandlerException('An error occurred while fetching the tags!');
        }
    }

    /**
     * Run a git command to fetch data from the repository en get the output (STDOUT).
     *
     * If returning an array, each element will contain one line of the trimmed output.
     *
     * Note: Command arguments with an empty value are removed from the argument list.
     *
     * @param   array  $arguments  The command arguments, listed as separate entries.
     * @param   bool   $asArray    True to return the output as an array, False to return the output as a string.
     *
     * @return array|string The output (STDOUT) of the process.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If the process didn't terminate successfully.
     */
    private function runGitCommand(array $arguments, bool $asArray)
    {
        // Prepare the process to run the command.
        $command = [$this->gitExecutable];
        if ($this->repoPath) {
            array_push($command, '--git-dir', "$this->repoPath.git");
        }
        $command = array_merge($command, $arguments);
        $process = new Process(array_values(array_filter($command)));

        // Start process and evaluate the output.
        try {
            $process->mustRun();
            $output = trim($process->getOutput());
        } catch (ProcessFailedException $e) {
            $process->getErrorOutput();
            throw new RepoHandlerException(
                "An error occurred while running a git command!\n" . strtok($process->getErrorOutput(), "\n")
            );
        }

        if ($asArray) {
            if ($output) {
                return explode("\n", trim($output));
            }

            return [];
        }

        return $output;
    }

    /**
     * Fetch and cache the commit data from the git repository.
     *
     * The tags are fetched from the repository, honoring the class-properties $options['fromTag'],
     * $options['toTag'] and $options['tagOrderBy'].
     *
     * @param   bool  $refresh  Set to true to refresh the cached commit data.
     *
     * @return array    The fetched commit data.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If addressing the repository fails.
     */
    public function fetchCommitData(bool $refresh): array
    {
        if (!$refresh && null !== $this->commitData) {
            // Return cached commit data.
            return $this->commitData;
        }

        $commitData = [];
        $tag        = '';
        $tagRange   = $this->options['toTag'];

        if ($this->options['fromTag']) {
            $rangeStart = $this->gitTags[ArrayUtils::arraySearch($this->options['fromTag'], $this->gitTags) + 1] ??
                          $tagRange;
            $tagRange   = "$rangeStart..$tagRange";
        }

        // Fetch commit data
        $tagData = $this->runGitCommand(
            [
                'log',
                $tagRange,
                '--pretty=format:%ad%x00%s%x00%h%x00%d%x00%p',
                '--date=short',
            ],
            true
        );

        foreach ($tagData as $line1) {
            $line = explode("\x00", $line1);
            if (preg_match('/tag: (.*?)[,)]/', $line[3] ?? '', $matches)) {
                $tag = $matches[1];
            }
            if (!$this->options['includeMergeCommits'] && false !== strpos($line[4] ?? '', ' ')) {
                // Skip merge commits.
                continue;
            }

            $commitData[$tag]['date']     = $commitData[$tag]['date'] ?? $line[0];
            $commitData[$tag]['titles'][] = $line[1];
            $commitData[$tag]['hashes'][] = $line[2];
        }

        // Cache commit data and process it.
        $this->commitData = $commitData;

        return $this->commitData;
    }

    /**
     * Get one or multiple options.
     *
     * A single option can be set by passing the option name as argument.
     * When you omit the name, an array with name-value pairs of all options is returned.
     *
     * @param   ?string  $name  [Optional] The name of the option.
     *
     * @return mixed Either the option value or name-value pairs of all options.
     *
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If the option doesn't exist.
     * @see \DigiLive\GitChangelog\RepoHandler\RepoHandler::$options
     */
    public function getOptions(?string $name = null)
    {
        if (null === $name) {
            return $this->options;
        }
        if (!array_key_exists($name, $this->options)) {
            throw new RepoHandlerException("Option '$name' does not exist!");
        }

        return $this->options[$name];
    }

    /**
     * Set one or multiple options.
     *
     * A single option can be set by passing the option name and value as arguments.
     * Alternatively you can set multiple options at once by passing a single argument as an array with option names
     * and values.
     *
     * @param   string|array  $name   The name of the option or an array of option name and values pairs.
     * @param   ?mixed        $value  [Optional] Value of option if the first parameter is of type string.
     *
     * @throws \OutOfBoundsException If the option you're trying to set is invalid.
     *
     * @see \DigiLive\GitChangelog\RepoHandler\RepoHandleroHandlerRepoHandler::$options
     */
    public function setOptions($name, $value = null): void
    {
        if (!is_array($name)) {
            $name = [$name => $value];
        }

        foreach ($name as $option => $value) {
            if (!array_key_exists($option, $this->options)) {
                throw new \OutOfBoundsException("Attempt to set an invalid option: $option!");
            }

            $this->options[$option] = $value;
        }
    }
}
