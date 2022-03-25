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
    use RendererTrait;

    /**
     * @var int Maximum length of the commit titles. Longer titles are word-wrapped, so they won't exceed this maximum.
     */
    public $titleLength = 80;

    /**
     * @var array Urls of the reference links currently in the changelog.
     */
    private $links = [];

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
        $logContent      = "# {$this->options['logHeader']}\n";

        if (!$commitData) {
            $this->changelog = "$logContent\n{$this->options['noChangesMessage']}\n";

            return;
        }

        if ('asc' == $this->options['tagOrder']) {
            $commitData = array_reverse($commitData);
        }

        foreach ($commitData as $tag => $data) {
            $logContent .= "\n";

            // Define the tag title and date, format and add them to the changelog.
            $tagData = [$tag, $data['date']];
            if ('' === $tag) {
                // Tag is HEAD revision.
                $tagData = [$this->options['headTagName'], $this->options['headTagDate']];
            }

            $logContent .= str_replace(['{tag}', '{date}'], $tagData, $this->formats['tag']) . "\n\n";

            if (!$data['titles']) {
                // No commit titles present for this tag.
                // Add a "No changes" messages to the changelog for the current tag and proceed with the next tag.
                $logContent .= rtrim(
                    str_replace(
                        ['{title}', '{hashes}'],
                        [$this->options['noChangesMessage'], ''],
                        $this->formats['title']
                    )
                );
                $logContent .= "\n";
                continue;
            }

            // Sort commit titles of the current tag.
            Utilities::natSort($data['titles'], $this->options['titleOrder']);

            // Add commit titles of the current tag to the changelog.
            $tagContent = '';
            foreach ($data['titles'] as $titleKey => $title) {
                // Convert issue and merge-request references into reference links.
                $title = $this->convertReferences($title, 'issue');
                $title = $this->convertReferences($title, 'mergeRequest');

                // Format the current commit title and corresponding hashes and add them to the changelog.
                $tagContent .= rtrim(
                    str_replace(
                        ['{title}', '{hashes}'],
                        [$title, $this->formatHashes($data['hashes'][$titleKey])],
                        $this->formats['title']
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
     * Convert issue or merge-request references in a line to a reference link.
     *
     * @param   string  $line           Line to convert.
     * @param   string  $referenceType  'issue' or 'mergeRequest'.
     *
     * @return string The line with converted issue or merge-request references.
     */
    private function convertReferences(string $line, string $referenceType): string
    {
        if ($this->urls[$referenceType]) {
            $line = preg_replace_callback(
                $this->patterns[$referenceType],
                function ($matches) use ($referenceType) {
                    /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
                    // @see https://youtrack.jetbrains.com/issue/WI-60248
                    $this->links[] = str_replace("{{$referenceType}}", $matches[1], $this->urls[$referenceType]);

                    return "[$matches[0]][{linkIndex}]";
                },
                $line
            );
        }

        return $line;
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

        if ($this->urls['commit']) {
            foreach ($hashes as &$hash) {
                $this->links[] = str_replace('{commit}', $hash, $this->urls['commit']);
                $hash          = "[$hash][{linkIndex}]";
            }

            unset($hash);
        }

        return '(' . implode(', ', $hashes) . ')';
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
        $linkIndex = 0;
        $pattern   = '/{linkIndex}/';

        // Reverse the content, links and search pattern at descending tag order.
        if ('desc' == $this->options['tagOrder']) {
            $content = array_reverse($content);
            $links   = array_values(array_reverse($links));
            $pattern = strrev($pattern);
        }

        // Replace placeholders with an index.
        foreach ($content as &$line) {
            // Reverse the order of the characters in the line at descending tag order.
            $line = 'desc' == $this->options['tagOrder'] ? strrev($line) : $line;
            $line = preg_replace_callback(
                $pattern,
                function () use (&$linkIndex): string {
                    return 'desc' == $this->options['tagOrder'] ? strrev((string) $linkIndex++) : (string) $linkIndex++;
                },
                $line
            );
            // Restore the character order of the line.
            $line = 'desc' == $this->options['tagOrder'] ? strrev($line) : $line;
        }
        unset($line);

        // Restore the order of the content at descending tag order.
        if ('desc' == $this->options['tagOrder']) {
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
