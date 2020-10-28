<?php

declare(strict_types=1);

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

namespace DigiLive\GitChangelog;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class GitChangelog
 *
 * Automatically generate a changelog build from git commits.
 * The log includes tags and their date, followed by the subject of each commit which belongs to that tag.
 *
 * In order to create a suitable changelog, you should follow the following guidelines:
 *
 * - Commit messages must have a subject line and may have body copy. These must be separated by a blank line.
 * - The subject line must not exceed 50 characters.
 * - The subject line should be capitalized and must not end in a period.
 * - The subject line must be written in an imperative mood (Fix, not Fixed / Fixes etc.).
 * - The body copy must be wrapped at 72 columns.
 * - The body copy must only contain explanations as to what and why, never how.
 *   The latter belongs in documentation and implementation.
 *
 * Subject Line Standard Terminology:
 * First Word   Meaning
 * Add          Create a capability e.g. feature, test, dependency.
 * Cut          Remove a capability e.g. feature, test, dependency.
 * Fix          Fix an issue e.g. bug, typo, accident, misstatement.
 * Bump         Increase the version of something e.g. dependency.
 * Make         Change the build process, or tooling, or infra.
 * Start        Begin doing something; e.g. create a feature flag.
 * Stop         End doing something; e.g. remove a feature flag.
 * Refactor     A code change that MUST be just a refactoring.
 * Reformat     Refactor of formatting, e.g. omit whitespace.
 * Optimize     Refactor of performance, e.g. speed up code.
 * Document     Refactor of documentation, e.g. help files.
 *
 * Subject lines must never contain (and / or start with) anything else.
 *
 * @package DigiLive\GitChangelog
 */
class GitChangelog
{
    /**
     * @var string Path to a base (changelog) file. The generated changelog can be prepend this file.
     */
    public $baseFile;
    /**
     * @var string Path to local git repository. Leave null for repository at current folder.
     */
    public $gitPath;
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
     *  headSubject         Subject of the HEAD revision (Implies unreleased commits).
     *  nextTagDate         Date at head subject (Implies date of next release).
     *  noChangesMessage    Message to show when there are no commit subjects to list for a tag.
     *  addHashes           True includes commit hashes to the listed subjects.
     *  includeMergeCommits True includes merge commits in the subject lists.
     *  tagOrderBy          Specify on which field the fetched tags have to be ordered.
     *  tagOrderDesc        True to sort the tags in descending order.
     *  commitOrder         Set to 'ASC' or 'DESC' to sort the subjects in resp. ascending/descending order.
     *  </pre>
     * @see https://git-scm.com/docs/git-for-each-ref
     */
    protected $options = [
        'logHeader'           => 'Changelog',
        'headSubject'         => 'Upcoming changes',
        'nextTagDate'         => 'Undetermined',
        'noChangesMessage'    => 'No changes.',
        'addHashes'           => true,
        'includeMergeCommits' => false,
        'tagOrderBy'          => 'creatordate',
        'tagOrderDesc'        => true,
        'commitOrder'         => 'ASC',
    ];
    /**
     * @var string Value of the oldest tag to include into the generated changelog.
     * @see GitChangelog::setFromTag()
     */
    private $fromTag;
    /**
     * @var string Value of the newest tag to include into the generated changelog.
     * @see GitChangelog::setToTag()
     */
    private $toTag = 'HEAD';
    /**
     * @var array Contains the tags which exist in the git repository.
     * @see GitChangelog::fetchTags();
     */
    private $gitTags;
    /**
     * @var string[] Contains the labels to filter the commit subjects. All subjects which do not start with any of
     *               these labels will not be listed. To disable this filtering, remove all labels from this variable.
     */
    private $labels = [
//        'Add',          // Create a capability e.g. feature, test, dependency.
//        'Cut',          // Remove a capability e.g. feature, test, dependency.
//        'Fix',          // Fix an issue e.g. bug, typo, accident, misstatement.
//        'Bump',         // Increase the version of something e.g. dependency.
//        'Make',         // Change the build process, or tooling, or infra.
//        'Start',        // Begin doing something; e.g. create a feature flag.
//        'Stop',         // End doing something; e.g. remove a feature flag.
//        'Refactor',     // A code change that MUST be just a refactoring.
//        'Reformat',     // Refactor of formatting, e.g. omit whitespace.
//        'Optimize',     // Refactor of performance, e.g. speed up code.
//        'Document',     // Refactor of documentation, e.g. help files.
    ];

    /**
     * GitChangelog constructor.
     *
     * @throws Exception When the defined From- or To-tag doesn't exist in the git repository.
     */
    public function __construct()
    {
        $this->fetchTags();
    }

    /**
     * Fetch all tags from the git repository.
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
     * @throws Exception When the defined From- or To-tag doesn't exist in the git repository.
     */
    public function fetchTags($force = false): array
    {
        // Return cached results unless forced to update.
        if (!$force && $this->gitTags !== null) {
            return $this->gitTags;
        }

        $gitPath = '--git-dir ';
        $gitPath .= $this->gitPath ?? './.git';

        // Get all git tags.
        $this->gitTags = explode("\n", shell_exec("git $gitPath tag --sort=-{$this->options['tagOrderBy']}"));
        array_pop($this->gitTags); // Remove empty trailing element.
        // Add HEAD revision as tag.
        if ($this->toTag == 'HEAD') {
            array_unshift($this->gitTags, 'HEAD');
        }

        $toKey  = $this->toTag == 'HEAD' ? 0 : Utilities::arraySearch($this->toTag, $this->gitTags);
        $length = $this->fromTag === null ? null : Utilities::arraySearch($this->fromTag, $this->gitTags) - $toKey + 1;

        // Cache requested git tags. $this->gitTags = [newest..oldest].
        $this->gitTags = array_slice($this->gitTags, $toKey, $length);

        return $this->gitTags;
    }

    /**
     * Fetch the commit data from the git repository.
     *
     * Commit data is formatted as follows after it is processed:
     *
     * [
     *     Tag => [
     *         'date'           => string,
     *         'uniqueSubjects' => [],
     *         'hashes'         => []
     * ]
     *
     * Note:
     * Re-calling this method will not overwrite the commit data which was retrieved at an earlier call.
     * To force a refresh, set the parameter to true.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param   false  $force  [Optional] Set to true to refresh the cached tags.
     *
     * @return array    Commit data.
     * @throws Exception When the defined From- or To-tag doesn't exist in the git repository.
     * @see GitChangelog::processCommitData()
     */
    public function fetchCommitData($force = false): array
    {
        // Return cached results unless forced to update.
        if (!$force && $this->commitData !== null) {
            return $this->commitData;
        }

        $gitTags     = $this->fetchTags();
        $previousTag = $this->toTag;
        $commitData  = [];

        // Add empty tag to get commits upto first tag or HEAD revision.
        if ($this->fromTag === null) {
            $gitTags[] = '';
        }

        $gitPath = '--git-dir ';
        $gitPath .= ($this->gitPath ?? './') . '.git';

        // Get tag dates and commit subjects from git log for each tag.
        $includeMergeCommits = $this->options['includeMergeCommits'] ? '' : '--no-merges';
        foreach ($gitTags as $tag) {
            $tagRange = $tag == '' ? $previousTag : "$tag...$previousTag";

            $commitData[$previousTag]['date']     =
                shell_exec("git $gitPath log -1 --pretty=format:%ad --date=short $previousTag");
            $commitData[$previousTag]['subjects'] =
                explode(
                    "\n",
                    shell_exec("git $gitPath log $tagRange $includeMergeCommits --pretty=format:%s") ?? ''
                );
            $commitData[$previousTag]['hashes']   =
                explode(
                    "\n",
                    shell_exec("git $gitPath log $tagRange $includeMergeCommits --pretty=format:%h") ?? ''
                );
            $previousTag                          = $tag;
        }

        // Cache commit data and process it.
        $this->commitData = $commitData;
        $this->processCommitData();

        return $this->commitData;
    }

    /**
     * Process the cached commit data.
     *
     * - Duplicate commit subjects are removed from the data.
     *   Commit hashes of these duplicates are added to the unique remaining subject.
     *
     * - Commit subjects which do not start with any of the defined labels are removed from the data also, unless no
     *   labels are defined at all.
     *
     * @see GitChangelog::fetchCommitData()
     * @see GitChangelog::$commitData
     */
    private function processCommitData(): void
    {
        foreach ($this->commitData as $tag => &$data) {
            // Merge duplicate subjects per tag.
            foreach ($data['subjects'] as $subjectKey => &$subject) {
                // Convert hash element into an array.
                $data['hashes'][$subjectKey] = [$data['hashes'][$subjectKey]];

                // Get indexes of all other elements with the same subject as the current one.
                $duplicates = array_keys($data['subjects'], $subject);
                array_shift($duplicates);

                // Add hashes of duplicate subjects to the current subject and remove this duplicates.
                // Subjects and hashes which belong to each other, have the same array key.
                foreach ($duplicates as $index) {
                    $data['hashes'][$subjectKey][] = $data['hashes'][$index];
                    unset($data['subjects'][$index], $data['hashes'][$index]);
                }

                // Remove subjects and hashes without specified labels.
                if ($this->labels && Utilities::arrayStrPos0($subject, $this->labels) === false) {
                    unset(
                        $this->commitData[$tag]['subjects'][$subjectKey],
                        $this->commitData[$tag]['hashes'][$subjectKey]
                    );
                }
            }
        }
    }

    /**
     * Save the generated changelog to a file.
     *
     * When a base file is defined, the content of the new file will be the content of this base file, prepended by the
     * generated changelog.
     *
     * Note:
     * This method will raise a warning when a base file is defined, but can not be read.
     *
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     *
     * @param   string  $filePath  Path to file to save the changelog.
     *
     * @throws RuntimeException When writing of the file fails.
     * @see GitChangelog::$baseFile
     */
    public function save(string $filePath): void
    {
        $baseContent = '';
        if ($this->baseFile !== null) {
            $baseContent = file_get_contents($this->baseFile);
        }

        if (@file_put_contents($filePath, $this->changelog . $baseContent) === false) {
            throw new RuntimeException('Unable to write to file!');
        }
    }

    /**
     * Get the content of the generated changelog.
     *
     * Optionally the changelog can prepend the content of a base file.
     *
     * Note:
     * This method will raise a warning when the base file, but can not be read.
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     *
     * @param   bool  $prepend  [Optional] Set to true to prepend the changelog to a base file.
     *
     * @return string The generated changelog, optionally followed by the content of a base file.
     * @see GitChangelog::$baseFile
     */
    public function get(bool $prepend = false): string
    {
        $baseContent = '';
        if ($prepend && $this->baseFile !== null) {
            $baseContent = file_get_contents($this->baseFile);
        }

        return $this->changelog . $baseContent;
    }

    /**
     * Set the newest git tag to include in the changelog.
     *
     * Omit or set to 'HEAD' or null to include the HEAD revision into the changelog.
     *
     * @param   mixed  $tag  Newest tag to include.
     *
     * @throws InvalidArgumentException When the tag does not exits in the repository.
     */
    public function setToTag($tag = null)
    {
        $tag = $tag ?? 'HEAD';
        Utilities::arraySearch($tag, $this->gitTags);
        $this->toTag = $tag;
    }

    /**
     * Set the oldest git tag to include in the changelog.
     *
     * Omit or set to null to include the oldest tag into the changelog.
     *
     * @param   mixed  $tag  Oldest tag to include.
     *
     * @throws InvalidArgumentException When the tag does not exits in the repository.
     */
    public function setFromTag($tag = null)
    {
        if ($tag !== null) {
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
            } catch (InvalidArgumentException $e) {
                continue;
            }
        }
        $this->labels = array_values($this->labels);
    }

    /**
     * Set filter labels.
     *
     * This method clear the existing labels and adds the parameter values as the new labels.
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
    public function setLabels(...$labels)
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
     * @param   string  ...$labels  Labels to add to the filter.
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
     * @param   mixed  $name   Name of option or array of option names and values.
     * @param   mixed  $value  [Optional] Value of option.
     *
     * @throws InvalidArgumentException If the option you're trying to set is invalid.
     * @see GitChangelog::$options
     */
    public function setOptions($name, $value = null)
    {
        if (!is_array($name)) {
            $name = [$name => $value];
        }

        foreach ($name as $option => $value) {
            if (!array_key_exists($option, $this->options)) {
                throw new InvalidArgumentException('Attempt to set an invalid option!');
            }

            $this->options[$option] = $value;
        }
    }
}