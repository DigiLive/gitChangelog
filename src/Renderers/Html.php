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
 * Class Html
 *
 * Renderer for GitChangelog.
 * The rendered changelog is formatted in markdown.
 */
class Html extends GitChangelog implements RendererInterface
{
    use RendererTrait;

    /**
     * Generate the changelog.
     *
     * The generated changelog will be stored into a class property.
     *
     * @throws \DigiLive\GitChangelog\GitChangelogException When fetching the commit data from the repository fails.
     */
    public function build(): void
    {
        $logContent = "<h1>{$this->options['logHeader']}</h1>\n";

        $commitData = $this->fetchCommitData();

        if (!$commitData) {
            $this->changelog = "$logContent<p>{$this->options['noChangesMessage']}</p>\n";

            return;
        }

        if ('asc' == $this->options['tagOrder']) {
            $commitData = array_reverse($commitData);
        }

        foreach ($commitData as $tag => $data) {
            // Add tag header and date.
            if ('' === $tag) {
                $tag          = $this->options['headTagName'];
                $data['date'] = $this->options['headTagDate'];
            }

            $logContent .= "\n<h2>$tag ({$data['date']})</h2>\n<ul>\n";

            // No commit titles present for this tag.
            if (!$data['titles']) {
                $logContent .= "    <li>{$this->options['noChangesMessage']}</li>\n</ul>\n";
                continue;
            }

            // Sort commit titles.
            Utilities::natSort($data['titles'], $this->options['titleOrder']);

            // Add commit titles to changelog.
            foreach ($data['titles'] as $titleKey => $title) {
                // Convert issue and merge-request references into links.
                $title = $this->convertReferences($title, 'issue');
                $title = $this->convertReferences($title, 'mergeRequest');

                // Format commit title and hashes.
                $logContent .= "    <li>$title " . $this->formatHashes($data['hashes'][$titleKey]) . "</li>\n";
            }

            $logContent .= "</ul>\n";
        }

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
            /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
            // @see https://youtrack.jetbrains.com/issue/WI-60248
            $line = preg_replace(
                $this->patterns[$referenceType],
                '<a href="' . str_replace("{{$referenceType}}", '$1', $this->urls[$referenceType]) . '">$0</a>',
                $line
            );
        }

        return $line;
    }

    /**
     * Format the hashes of a commit title into a string.
     *
     * Each hash is formatted into a link as defined by property commitUrl.
     * After formatting, all hashes are concatenated to a single line, comma separated.
     *
     * @param   array  $hashes  Hashes to format
     *
     * @return string Formatted hash string.
     * @see GitChangelog::$commitUrl
     */
    protected function formatHashes(array $hashes): string
    {
        if (!$this->options['addHashes']) {
            return '';
        }

        if (null !== $this->urls['commit']) {
            foreach ($hashes as &$hash) {
                $hash = '<a href="' . str_replace('{commit}', $hash, $this->urls['commit']) . "\">$hash</a>";
            }

            unset($hash);
        }

        return '(' . implode(', ', $hashes) . ')';
    }
}
