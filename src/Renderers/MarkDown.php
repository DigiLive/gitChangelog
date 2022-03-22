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

namespace DigiLive\GitChangelog\Renderers;

use DigiLive\GitChangelog\GitChangelog;
use DigiLive\GitChangelog\Utilities;

/**
 * Class MarkDown
 *
 * Renderer for GitChangelog.
 * The rendered changelog is formatted in markdown.
 */
class MarkDown extends GitChangelog implements RendererInterface
{
    /**
     * @var string Format of tag strings. {tag} is replaced by the tags found in the git log, {date} is replaced by the
     *             corresponding tag date.
     */
    public $formatTag = '## {tag} ({date})';
    /**
     * @var string Format of titles. {title} is replaced by commit titles, {hashes} is replaced by the formatted
     *             commit hashes.
     */
    public $formatTitle = '* {title} {hashes}';
    /**
     * @var string Url to commit view of the remote repository. If set, hashes of commit titles are converted into
     *             links which refer to the corresponding commit at the remote.
     *             {hash} is replaced by the commits hash id.
     */
    public $commitUrl;
    /**
     * @var string Url to Issue tracker of the repository. If set, issue references in commit title are converted into
     *             links which refers to the corresponding issue at the tracker.
     *             {issue} is replaced by the issue number.
     */
    public $issueUrl;
    /**
     * @var int Maximum length of the commit titles. Longer titles are word-wrapped, so they won't exceed this maximum.
     */
    public $titleLength = 80;

    /**
     * @var array Urls of the reference links currently in the changelog.
     */
    private $links = [];
    /**
     * @var int Current amount of reference links in the changelog.
     */
    private $linkCount = 0;

    /**
     * Generate the changelog.
     *
     * The generated changelog will be stored into a class property.
     *
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the commit data of the repository fails.
     */
    public function build(): void
    {
        $commitData      = $this->fetchCommitData();
        $this->links     = [];
        $this->linkCount = 0;
        $logContent      = "# {$this->options['logHeader']}\n";

        if (!$commitData) {
            $this->changelog = "$logContent\n{$this->options['noChangesMessage']}\n";

            return;
        }

        if (!$this->options['tagOrderDesc']) {
            $commitData = array_reverse($commitData);
        }

        foreach ($commitData as $tag => $data) {
            $logContent .= "\n";

            // Define the tag title and date, format and add them to the changelog.
            $tagData = [$tag, $data['date']];
            if ('' === $tag) {
                $tagData = [$this->options['headTagName'], $this->options['headTagDate']];
            }

            $logContent .= str_replace(['{tag}', '{date}'], $tagData, $this->formatTag) . "\n\n";

            // No titles present for this tag.
            // Add a "No changes" messages to the changelog for the current tag and proceed with the next tag.
            if (!$data['titles']) {
                $logContent .= rtrim(
                    str_replace(['{title}', '{hashes}'], [$this->options['noChangesMessage'], ''], $this->formatTitle)
                );
                $logContent .= "\n";
                continue;
            }

            // Sort commit titles of the current tag.
            Utilities::natSort($data['titles'], $this->options['titleOrder']);

            // Add commit titles of the current tag to the changelog.
            $tagContent = '';
            foreach ($data['titles'] as $titleKey => &$title) {
                // Format issue identifiers of the current commit title into an url, if the url is defined.
                if (null !== $this->issueUrl) {
                    $title = preg_replace_callback(
                        '/#(\d+)/',
                        function ($matches): string {
                            $this->links[] = str_replace('{issue}', $matches[1], $this->issueUrl);

                            return "[$matches[0]][{linkIndex}]";
                        },
                        $title
                    );
                }

                // Format the current commit title and corresponding hashes and add them to the changelog.
                $tagContent .= rtrim(
                    str_replace(
                        ['{title}', '{hashes}'],
                        [$title, $this->formatHashes($data['hashes'][$titleKey])],
                        $this->formatTitle
                    )
                );
                $tagContent .= "\n";
            }

            $logContent .= $this->wordWrap($tagContent);
        }

        // Inject reference links into the changelog.
        $logContent = $this->injectLinks($logContent);

        $this->changelog = $logContent;
    }

    /**
     * Format the hashes of a commit title into a string.
     *
     * Each hash is formatted into a reference link.
     * The referenced link is formatted as defined by property GitChangelog::$commitUrl and stored into property
     * GitChangelog::$links.
     * After formatting, all hashes are concatenated to a single string with a comma and surrounded parenthesis.
     *
     * @param   array  $hashes  The hashes to format.
     *
     * @return string A formatted hash string.
     * @see GitChangelog::$commitUrl
     * @see GitChangelog::$links
     */
    protected function formatHashes(array $hashes): string
    {
        if (!$this->options['addHashes']) {
            return '';
        }

        if (null !== $this->commitUrl) {
            foreach ($hashes as &$hash) {
                $this->links[] = str_replace('{hash}', $hash, $this->commitUrl);
                $hash          = "[$hash][{linkIndex}]";
            }

            unset($hash);
        }

        $hashes = implode(', ', $hashes);

        return "($hashes)";
    }

    /**
     * Word-wrap a string to a maximum of chars.
     *
     * @param   string  $content  The tring to wrap.
     *
     * @return string The wrapped string.
     */
    private function wordWrap(string $content): string
    {
        $wrappedContent = wordwrap($content, $this->titleLength);
        if ($wrappedContent != $content) {
            $wrappedContent = ltrim(preg_replace('/^(\d+\.|[-*+] )/m', "\n$1", $wrappedContent));
        }

        return $wrappedContent;
    }

    /**
     * Injects reference links into a string.
     *
     * Basically it replaces the {linkIndex} placeholders with an index number and adds a reference link at the end of
     * the string.
     *
     * @param   string  $content  The string to inject the reference links into.
     *
     * @return string The content with the injected reference links.
     */
    private function injectLinks(string $content): string
    {
        // Convert content into an array of lines.
        $content   = preg_split('/\R/', $content);
        $links     = $this->links;
        $linkIndex = $this->linkCount;
        $pattern   = '/{linkIndex}/';

        // Reverse the content, links and search pattern at descending tag order.
        if ($this->options['tagOrderDesc']) {
            $content = array_reverse($content);
            $links   = array_values(array_reverse($this->links));
            $pattern = strrev($pattern);
        }

        // Replace placeholders with an index.
        foreach ($content as &$line) {
            // Reverse the order of the characters in the line at descending tag order.
            $line = $this->options['tagOrderDesc'] ? strrev($line) : $line;
            $line = preg_replace_callback(
                $pattern,
                function () use (&$linkIndex): string {
                    return $this->options['tagOrderDesc'] ? strrev((string) $linkIndex++) : (string) $linkIndex++;
                },
                $line
            );
            // Restore the character order of the line.
            $line = $this->options['tagOrderDesc'] ? strrev($line) : $line;
        }
        unset($line);

        // Restore the order of the content at descending tag order.
        if ($this->options['tagOrderDesc']) {
            $content = array_reverse($content);
        }

        // Restore the order of the content and convert it back to a string.
        $content = implode("\n", $content) . "\n";

        // Append the indexed reference links to the content.
        foreach ($links as $index => $link) {
            $content .= "[$index]:$link\n";
        }

        return $content;
    }
}
