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

namespace DigiLive\GitChangelog\GitChangelog;

use DigiLive\GitChangelog\RepoHandler\RepoHandler;
use DigiLive\GitChangelog\Utilities\ArrayUtils;

/**
 * Git Changelog generator
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
     * @var string The generated changelog.
     * @see \DigiLive\GitChangelog\GitChangelog\Renderers\RendererInterface::build()
     */
    protected $changelog;
    /**
     * @var array Contains the processed commit data which is fetched from the git repository.
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
     *  tagOrder            Set to 'asc' or 'desc' (case insensitive) to sort the tags in resp. ascending/descending
     *                      order. Any other value defaults to 'desc'.
     *  titleOrder          Set to 'asc' or 'desc' (case insensitive) to sort the titles in resp. ascending/descending
     *                      order. Any other value leaves the order untouched.
     *  similarityThreshold Set to a value between 0 and 1 to merge similar commit titles together.
     *                      0.0 equates no similarity, 1.0 is an exact match.
     *  includeMergeCommits True includes merge commits in the title lists.
     *  tagOrderBy          Specify on which field the fetched tags have to be ordered.
     *  </pre>
     * @see https://git-scm.com/docs/git-for-each-ref
     */
    protected $options = [
        // Generator options.
        'logHeader'           => 'Changelog',
        'headTagName'         => 'Upcoming changes',
        'headTagDate'         => 'Undetermined',
        'noChangesMessage'    => 'No changes.',
        'addHashes'           => true,
        'tagOrder'            => 'desc',
        'titleOrder'          => 'asc',
        'similarityThreshold' => 1,
        // Repository options.
        'includeMergeCommits' => false,
        'tagOrderBy'          => 'creatordate',
    ];
    /**
     * @var string[] Contains the labels to filter the commit titles.
     *               Only titles which start with any of these labels will be listed.
     *               To disable this filtering, remove all labels from this property.
     */
    protected $labels = [
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
     * @var \DigiLive\GitChangelog\RepoHandler\RepoHandler Handler which addresses the repository.
     */
    private $repoHandler;

    /**
     * Constructor.
     *
     * @param   ?string  $repoPath  Path to the repository directory.
     *
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
     *
     * All git tags are pre-fetched from the repository at the given path.
     *
     */
    public function __construct(?string $repoPath = null)
    {
        $this->repoHandler = new RepoHandler($repoPath);
        $this->repoHandler->setOptions(array_slice($this->options, -2, 2, true));
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
     * @throws \OutOfBoundsException If the option you're trying to set, doesn't exist.
     * @throws \RangeException If setting option 'headTagName' to an invalid value.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
     *
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

            if ('headTagName' == $option && in_array($value, $this->repoHandler->fetchTags(false))) {
                throw new \RangeException('Attempt to set option headTagName to an already existing tag value!');
            }

            $this->options[$option] = $value;
        }

        // Apply repository potions to handler.
        $this->repoHandler->setOptions(array_slice($this->options, -2, 2, true));
    }

    /**
     * Get the content of the generated changelog.
     *
     * @return string The content of the generated changelog.
     */
    public function get(): string
    {
        return $this->changelog;
    }

    /**
     * Set the oldest git tag to include in the changelog.
     *
     * Omit or set to null to include the oldest repository tag.
     *
     * Note: This method does not affect the contents of the cached fetched tags.
     *
     * @param   ?string  $tag  The oldest tag to include.
     *
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
     * @throws \OutOfBoundsException If the tag doesn't exist in the repository.
     *
     * @see GitChangelog::fetchTags()
     */
    public function setFromTag(?string $tag = null): void
    {
        if (null !== $tag) {
            ArrayUtils::arraySearch($tag, $this->repoHandler->fetchTags(false));
        }

        $this->repoHandler->setOptions('fromTag', $tag);
    }

    /**
     * Set the newest git tag to include in the changelog.
     *
     * Omit or set to '' or null to include the HEAD revision of the checked-out branch into the changelog.
     *
     * Note: This method does not affect the contents of the cached fetched tags.
     *
     * @param   ?string  $tag  The newest tag to include.
     *
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If fetching the repository tags fails.
     * @throws \OutOfBoundsException If the tag doesn't exist in the repository.
     *
     * @see GitChangelog::fetchTags()
     */
    public function setToTag(?string $tag = null): void
    {
        if (null !== $tag) {
            ArrayUtils::arraySearch($tag, $this->repoHandler->fetchTags(false));
        }

        $this->repoHandler->setOptions('toTag', $tag);
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
                $key = ArrayUtils::arraySearch($label, $this->labels);
                unset($this->labels[$key]);
            } catch (\OutOfBoundsException $e) {
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
            $this->labels[] = (string) $label;
        }

        $this->labels = array_unique($this->labels);
    }

    /**
     * Get the processed commit data of the repository.
     *
     * @param   bool  $refresh  Set to true to refresh the cached commit data.
     *
     * @return array The processed commit data.
     * @throws \DigiLive\GitChangelog\RepoHandler\RepoHandlerException If addressing the repository fails.
     */
    protected function getCommitData(bool $refresh): array
    {
        if (!$refresh && null !== $this->commitData) {
            // Return cached processed commit data.
            return $this->commitData;
        }

        // Re-fetch the commit data from the repository and process this data.
        $this->commitData = $this->repoHandler->fetchCommitData($refresh);
        $this->processCommitData();

        return $this->commitData;
    }

    /**
     * Process the cached commit data.
     *
     * - Duplicate commit titles are merged together.
     *   Commit hashes of these duplicates are added to the unique remaining title.
     *
     * - Commit titles which do not start with any of the defined labels are removed from the data also, unless no
     *   labels are defined at all.
     *
     * @see \DigiLive\GitChangelog\RepoHandler\RepoHandler::fetchCommitData()
     * @see \DigiLive\GitChangelog\RepoHandler\RepoHandler::$commitData
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

                // Get indexes of all other elements with a similar title as the current one.
                $duplicates = ArrayUtils::findSimilarKeys(
                    $data['titles'],
                    $title,
                    $this->options['similarityThreshold']
                );
                array_shift($duplicates);

                // Add hashes of duplicate titles to the current title and remove this duplicates.
                // Titles and hashes which belong to each other, have the same array key.
                foreach ($duplicates as $index) {
                    $data['hashes'][$titleKey][] = $data['hashes'][$index];
                    unset($data['titles'][$index], $data['hashes'][$index]);
                }

                // Remove titles and hashes without specified labels.
                if ($this->labels && false === ArrayUtils::arrayStrPos0($title, $this->labels)) {
                    unset(
                        $this->commitData[$tag]['titles'][$titleKey],
                        $this->commitData[$tag]['hashes'][$titleKey]
                    );
                }
            }
        }
    }
}
