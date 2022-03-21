<?php

/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2020, Ferry Cools (DigiLive)
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

namespace DigiLive\GitChangelog;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Class GitChangelog
 *
 * Automatically generate a changelog build from git commits.
 * The log includes tags and their date, followed by the title of each commit which belongs to that tag.
 *
 * In order to create a suitable changelog, you should follow the following guidelines:
 *
 * - Commit messages must have a title line and may have body copy. These must be separated by a blank line.
 * - The title line must not exceed 50 characters.
 * - The title line should be capitalized and must not end in a period.
 * - The title line must be written in an imperative mood (Fix, not Fixed / Fixes etc.).
 * - The body copy must be wrapped at 72 columns.
 * - The body copy must only contain explanations as to what and why, never how.
 *   The latter belongs in documentation and implementation.
 *
 * Title Line Standard Terminology:
 * First Word   Meaning
 * Add          Create a capability; E.g., feature, test, dependency.
 * Cut          Remove a capability; E.g., feature, test, dependency.
 * Fix          Fix an issue; E.g., bug, typo, accident, misstatement.
 * Bump         Increase the version of something E.g. dependency.
 * Make         Change the build process, tooling, or infra.
 * Start        Begin doing something; E.g., create a feature flag.
 * Stop         End doing something; E.g., remove a feature flag.
 * Refactor     A code change that MUST be just a refactoring.
 * Reformat     Refactor of formatting; E.g., omit whitespace.
 * Optimize     Refactor of performance; E.g., speed up code.
 * Document     Refactor of documentation; E.g., help files.
 *
 * Title lines must never contain (and / or start with) anything else.
 */
class GitChangelog
{
    /**
     * @var string Path to local git repository.
     */
    protected $repoPath;
    /**
     * @var string Base content to append to the generated changelog. If the value is a path which resolves to a file,
     *             append the content of this file.
     */
    protected $baseContent;
    /**
     * @var string The generated changelog.
     * @see GitChangelog::build()
     */
    protected $changelog;
    /**
     * @var array Contains the (processed) information which is fetched from the git repository.
     */
    protected $commitData;
    /**
     * @var array Contains the user options used by the class.
     *
     *  <pre>
     *  logHeader           First string of the generated changelog.
     *  headTagName         Name of the HEAD revision (Implies unreleased commits).
     *  headTagDate         Date of head revision (Implies date of next release).
     *  noChangesMessage    Message to show when there are no commit titles to list for a tag.
     *  addHashes           True includes commit hashes to the listed titles.
     *  includeMergeCommits True includes merge commits in the title lists.
     *  tagOrderBy          Specify on which field the fetched tags have to be ordered.
     *  tagOrderDesc        True to sort the tags in descending order.
     *  titleOrder          Set to 'ASC' or 'DESC' to sort the titles in resp. ascending/descending order.
     *  </pre>
     * @see https://git-scm.com/docs/git-for-each-ref
     */
    protected $options = [
        'logHeader'           => 'Changelog',
        'headTagName'         => 'Upcoming changes',
        'headTagDate'         => 'Undetermined',
        'noChangesMessage'    => 'No changes.',
        'addHashes'           => true,
        'includeMergeCommits' => false,
        'tagOrderBy'          => 'creatordate',
        'tagOrderDesc'        => true,
        'titleOrder'          => 'ASC',
    ];
    /**
     * @var string Value of the oldest tag to include into the generated changelog.
     *             If the value is null it refers to the oldest commit.
     * @see GitChangelog::setFromTag()
     */
    private $fromTag;
    /**
     * @var string Value of the newest tag to include into the generated changelog.
     *             If the value is null, it refers to the HEAD revision.
     * @see GitChangelog::setToTag()
     */
    private $toTag;
    /**
     * @var array Contains the tags which exist in the git repository.
     *            If the first element's key is null, it refers to the HEAD revision.
     * @see GitChangelog::fetchTags();
     */
    private $gitTags;
    /**
     * @var string[] Contains the labels to filter the commit titles.
     *               Only titles which start with any of these labels will be listed.
     *               To disable this filtering, remove all labels from this property.
     */
    private $labels = [
        // 'Add',          // Create a capability e.g. feature, test, dependency.
        // 'Cut',          // Remove a capability e.g. feature, test, dependency.
        // 'Fix',          // Fix an issue e.g. bug, typo, accident, misstatement.
        // 'Bump',         // Increase the version of something e.g. dependency.
        // 'Make',         // Change the build process, or tooling, or infra.
        // 'Start',        // Begin doing something; e.g. create a feature flag.
        // 'Stop',         // End doing something; e.g. remove a feature flag.
        // 'Refactor',     // A code change that MUST be just a refactoring.
        // 'Reformat',     // Refactor of formatting, e.g. omit whitespace.
        // 'Optimize',     // Refactor of performance, e.g. speed up code.
        // 'Document',     // Refactor of documentation, e.g. help files.
    ];
    /**
     * @var string|null Path to the Git executable. If not found automatically, it assumed the path is included in the
     *                  systems' environment variables.
     */
    private $gitExecutable;

    /**
     * GitChangelog constructor.
     *
     * All git tags are pre-fetched from the repository at the given path.
     *
     * @param   string  $repoPath  Path to the repository directory.
     *
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the repository tags fails.
     */
    public function __construct(string $repoPath = './')
    {
        $this->repoPath      = $repoPath;
        $executableFinder    = new ExecutableFinder();
        $this->gitExecutable = $executableFinder->find('git', 'git');

        $this->fetchTags();
    }

    /**
     * Fetch tags from the git repository.
     *
     * Note:
     * Re-calling this method will not overwrite the tags which where retrieved at an earlier call.
     * To force a refresh, set the parameter to true.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param   bool  $force  [Optional] Set to true to refresh the cached tags.
     *
     * @return array The cached tags.
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the tags fails.
     */
    public function fetchTags(bool $force = false): array
    {
        // Return cached results unless forced to update.
        if (!$force && null !== $this->gitTags) {
            return $this->gitTags;
        }

        $this->gitTags = [];
        $command       = [
            $this->gitExecutable,
            '--git-dir',
            "$this->repoPath.git",
            'tag',
            '--sort',
            "-{$this->options['tagOrderBy']}",
        ];

        try {
            $this->gitTags = $this->runCommand($command, true);
            // Add HEAD revision as tag.
            if ($this->toTag === null) {
                array_unshift($this->gitTags, $this->toTag);
            }

            $toKey  = Utilities::arraySearch($this->toTag, $this->gitTags);
            $length = null;
            if (null !== $this->fromTag) {
                $length = Utilities::arraySearch($this->fromTag, $this->gitTags) - $toKey + 1;
            }

            // Cache requested git tags. $this->gitTags = [newest..oldest].
            $this->gitTags = array_slice($this->gitTags, $toKey, $length);
        } catch (GitChangelogException $e) {
            throw new GitChangelogException('An error occurred while fetching the tags from the repository!');
        }

        return $this->gitTags;
    }

    /**
     * Fetch the commit data from the git repository.
     *
     * The tags are re-fetched from the repository, honoring the properties GitChangelog::$fromTag,
     * GitChangelog::$endTag and GitChangelog::options['tagOrderBy'].
     * Afterwards, the commit data of the re-fetched tags is fetched from the repository.
     *
     * Commit data is formatted as follows after it is processed:
     *
     * [
     *     Tag => [
     *         'date'         => string,
     *         'uniqueTitles' => string[],
     *         'hashes'       => string[]
     * ]
     *
     * Note:
     * Re-calling this method will not overwrite the commit data which was retrieved at an earlier call.
     * To force a refresh, set the parameter to true.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param   false  $force  [Optional] Set to true to refresh the cached commit data.
     *
     * @return array    Commit data.
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the repository tags fails.
     * @see GitChangelog::processCommitData()
     */
    public function fetchCommitData(bool $force = false): array
    {
        // Return cached results unless forced to update.
        if (!$force && $this->commitData !== null) {
            return $this->commitData;
        }

        // Re-fetch tags because tag range and order can be altered after pre-fetch.
        $gitTags    = $this->fetchTags(true);
        $commitData = [];
        $includeMerges = $this->options['includeMergeCommits'] ? null : '--no-merges';

        // Fetch dates and commit titles/hashes from git log for each tag.
        foreach ($gitTags as $tag) {
            $rangeStart = next($gitTags);
            $tagRange   = false !== $rangeStart ? "$rangeStart..$tag" : "$tag";

            // Fetch tag dates.
            $commitData[$tag]['date'] = $this->runCommand([
                $this->gitExecutable,
                '--git-dir',
                "$this->repoPath.git",
                'log',
                '-1',
                '--pretty=format:%ad',
                '--date=short',
                $tag,
            ], false);

            // Fetch commit titles.
            $commitData[$tag]['titles'] = $this->runCommand([
                $this->gitExecutable,
                '--git-dir',
                "$this->repoPath.git",
                'log',
                $tagRange,
                '--pretty=format:%s',
                $includeMerges,
            ], true);

            // Fetch commit hashes.
            $commitData[$tag]['hashes'] = $this->runCommand([
                $this->gitExecutable,
                '--git-dir',
                "$this->repoPath.git",
                'log',
                $tagRange,
                '--pretty=format:%h',
                $includeMerges,
            ], true);
        }

        // Cache commit data and process it.
        $this->commitData = $commitData;
        $this->processCommitData();

        return $this->commitData;
    }

    /**
     * Process the cached commit data.
     *
     * - Duplicate commit titles are removed from the data.
     *   Commit hashes of these duplicates are added to the unique remaining title.
     *
     * - Commit titles which do not start with any of the defined labels are removed from the data also, unless no
     *   labels are defined at all.
     *
     * @see GitChangelog::fetchCommitData()
     * @see GitChangelog::$commitData
     */
    private function processCommitData(): void
    {
        foreach ($this->commitData as $tag => &$data) {
            // Merge duplicate titles per tag.
            /* @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
            // https://youtrack.jetbrains.com/issue/WI-56632
            foreach ($data['titles'] as $titleKey => &$title) {
                // Convert hash element into an array.
                $data['hashes'][$titleKey] = [$data['hashes'][$titleKey]];

                // Get indexes of all other elements with the same title as the current one.
                $duplicates = array_keys($data['titles'], $title);
                array_shift($duplicates);

                // Add hashes of duplicate titles to the current title and remove this duplicates.
                // Titles and hashes which belong to each other, have the same array key.
                foreach ($duplicates as $index) {
                    $data['hashes'][$titleKey][] = $data['hashes'][$index];
                    unset($data['titles'][$index], $data['hashes'][$index]);
                }

                // Remove titles and hashes without specified labels.
                if ($this->labels && false === Utilities::arrayStrPos0($title, $this->labels)) {
                    unset(
                        $this->commitData[$tag]['titles'][$titleKey],
                        $this->commitData[$tag]['hashes'][$titleKey]
                    );
                }
            }
        }
    }

    /**
     * Save the generated changelog to a file.
     *
     * When property GitChangelog::$baseContent is set, the content of the saved file consists of the generated
     * changelog, appended by the base content.
     *
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     *
     * @param   string  $filePath  Path to file to save the changelog.
     *
     * @throws \DigiLive\GitChangelog\GitChangelogException When writing of the file fails.
     * @see GitChangelog::$baseContent
     */
    public function save(string $filePath): void
    {
        if (@file_put_contents($filePath, $this->changelog . $this->baseContent) === false) {
            throw new GitChangelogException('Unable to write to file!');
        }
    }

    /**
     * Get the content of the generated changelog.
     *
     * Optionally the generated changelog is appended with the content of property GitChangelog::$baseContent.
     *
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     *
     * @param   bool  $append  [Optional] Set to true to append the changelog with base content.
     *
     * @return string The generated changelog, optionally followed by base content.
     * @see GitChangelog::$baseContent
     */
    public function get(bool $append = false): string
    {
        return $this->changelog . ($append ? $this->baseContent : '');
    }

    /**
     * Set base content for the generated changelog.
     *
     * If a base content is set, this content is appended to the generated changelog.
     * If the argument resolves to a valid filepath, the contents of this file is used as base content.
     * Otherwise, the argument's value is considered to be the base content.
     *
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     *
     * @param   string  $content  Filepath or base content.
     */
    public function setBaseContent(string $content): void
    {
        $fileContent       = @file_get_contents($content);
        $this->baseContent = false !== $fileContent ? $fileContent : $content;
    }

    /**
     * Set the newest git tag to include in the changelog.
     *
     * Omit or set to '' or null to include the HEAD revision into the changelog.
     *
     * Note: This method does not affect the contents of the pre-fetched tags.
     *
     * @param   mixed  $tag  The newest tag to include.
     *
     * @see GitChangelog::fetchTags()
     */
    public function setToTag($tag = null): void
    {
        $tag = $tag ?? '';
        Utilities::arraySearch($tag, $this->gitTags);
        $this->toTag = $tag;
    }

    /**
     * Set the oldest git tag to include in the changelog.
     *
     * Omit or set to null to include the oldest tag into the changelog.
     *
     * Note: This method does not affect the contents of the pre-fetched tags.
     *
     * @param   mixed  $tag  The oldest tag to include.
     *
     * @see GitChangelog::fetchTags()
     */
    public function setFromTag($tag = null): void
    {
        if (null !== $tag) {
            Utilities::arraySearch($tag, $this->gitTags);
        }

        $this->fromTag = $tag;
    }

    /**
     * Remove a label from the filter.
     *
     * Declare a value as parameter for each label you want to remove.
     * You can also pass an array of labels, using the splat operator.
     * E.g. ...['label1', 'label2']
     *
     * @param   string  ...$labels  Labels to remove from the filter.
     *
     * @see GitChangelog::processCommitData()
     */
    public function removeLabel(string ...$labels): void
    {
        foreach ($labels as $label) {
            try {
                $key = Utilities::arraySearch($label, $this->labels);
                unset($this->labels[$key]);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        $this->labels = array_values($this->labels);
    }

    /**
     * Set filter labels.
     *
     * This method clears the existing labels and adds the parameter values as the new labels.
     *
     * Declare a value as parameter for each label you want to set.
     * You can also pass an array of labels, using the splat operator.
     * E.g. ...['label1', 'label2']
     *
     * Note:
     * This method raises a notice on array values or fatal error on objects that don't implement the __toString()
     * method.
     *
     * @param   string  ...$labels  Labels to set as filter.
     *
     * @see GitChangelog::processCommitData()
     */
    public function setLabels(...$labels): void
    {
        $this->labels = [];
        $this->addLabel(...$labels);
    }

    /**
     * Add a label to the filter.
     *
     * Declare a value as parameter for each label you want to add.
     * You can also pass an array of labels, using the splat operator.
     * E.g. ...['label1', 'label2']
     *
     * Note:
     * This method raises a notice on array values or fatal error on objects that don't implement the __toString()
     * method.
     *
     * @param   string|string[]  ...$labels  Labels to add to the filter.
     *
     * @see GitChangelog::processCommitData()
     */
    public function addLabel(...$labels)
    {
        foreach ($labels as $label) {
            $this->labels[] = (string)$label;
        }

        $this->labels = array_unique($this->labels);
    }

    /**
     * Set one or multiple options.
     *
     * A single option can be set by passing the option name and value as arguments.
     * Alternatively you can set multiple options at once by passing a single argument as an array with option names
     * and values.
     *
     * Note: This method does not affect the contents of the pre-fetched tags.
     *
     * @param   mixed  $name   Name of option or array of option names and values.
     * @param   mixed  $value  [Optional] Value of option.
     *
     * @throws \OutOfBoundsException If the option you're trying to set is invalid.
     * @throws \OutOfRangeException When setting option 'headTag' to an invalid value.
     * @throws \DigiLive\GitChangelog\GitChangelogException If fetching the repository tags fails.
     * @see GitChangelog::$options
     * @see GitChangelog::fetchTags()
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

            if ('headTagName' == $option && in_array($value, $this->fetchTags())) {
                throw new \OutOfRangeException('Attempt to set option headTagName to an already existing tag value!');
            }

            $this->options[$option] = $value;
        }
    }

    /**
     * Run a command to fetch data from the repository en get the output (STDOUT).
     *
     * If returning an array, each element will contain one line of the output.
     *
     * Note: Command arguments with an empty value are removed from the argument list.
     *
     * @param   array  $command  The command to run and its arguments listed as separate entries
     * @param   bool   $asArray  True to return the output as an array, False to return the output as a string.
     *
     * @return array|string The output (STDOUT) of the process.
     * @throws \DigiLive\GitChangelog\GitChangelogException When the process didn't terminate successfully.
     */
    private function runCommand(array $command, bool $asArray)
    {
        $process = new Process(array_values(array_filter($command)));

        try {
            $process->mustRun();
            $output = $process->getOutput();
        } catch (ProcessFailedException $e) {
            throw new GitChangelogException('An error occurred while fetching data from the repository!');
        }

        if ($asArray) {
            $output = explode("\n", trim($output));
        }

        return $output;
    }
}
