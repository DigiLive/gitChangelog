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

namespace DigiLive\GitChangelog\Renderers;

use DigiLive\GitChangelog\GitChangelog;
use DigiLive\GitChangelog\Utilities;
use Exception;

/**
 * Class MarkDown
 *
 * Renderer for GitChangelog.
 * The rendered changelog is formatted in markdown.
 *
 * @package DigiLive\GitChangelog\Renderers
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
     * Generate the changelog.
     *
     * The generated changelog will be stored into a class property.
     *
     * @throws Exception When the defined From- or To-tag doesn't exist in the git repository.
     */
    public function build(): void
    {
        $logContent = "# {$this->options['logHeader']}\n";

        $commitData = $this->fetchCommitData();

        if (!$commitData) {
            $this->changelog = "$logContent\n{$this->options['noChangesMessage']}\n";

            return;
        }

        if (!$this->options['tagOrderDesc']) {
            $commitData = array_reverse($commitData);
        }

        foreach ($commitData as $tag => $data) {
            $logContent .= "\n";
            // Add tag header and date.
            $tagData = [$tag, $data['date']];
            if ($tag === '') {
                $tagData = [$this->options['headTagName'], $this->options['headTagDate']];
            }

            $logContent .= str_replace(['{tag}', '{date}'], $tagData, $this->formatTag) . "\n\n";

            // No titles present for this tag.
            if (!$data['titles']) {
                $title      = $this->options['noChangesMessage'];
                $logContent .= rtrim(str_replace(['{title}', '{hashes}'], [$title, ''], $this->formatTitle));
                $logContent .= "\n";
                continue;
            }

            // Sort commit titles.
            Utilities::natSort($data['titles'], $this->options['titleOrder']);

            // Add commit titles.
            foreach ($data['titles'] as $titleKey => &$title) {
                if ($this->issueUrl !== null) {
                    $title = preg_replace(
                        '/#(\d+)/',
                        '[#$1](' . str_replace('{issue}', '$1', $this->issueUrl) . ')',
                        $title
                    );
                }
                $logContent .= rtrim(
                    str_replace(
                        ['{title}', '{hashes}'],
                        [$title, $this->formatHashes($data['hashes'][$titleKey])],
                        $this->formatTitle
                    )
                );
                $logContent .= "\n";
            }
        }

        $this->changelog = $logContent;
    }

    /**
     * Format the hashes of a commit title into a string.
     *
     * Each hash is formatted into a link as defined by property commitUrl.
     * After formatting, all hashes are concatenated to a single line, comma separated.
     * Finally, this line is formatted as defined by property formatHashes.
     *
     * @param   array  $hashes  Hashes to format
     *
     * @return string Formatted hash string.
     * @see GitChangelog::$commitUrl
     * @see GitChangelog::$formatHashes
     */
    protected function formatHashes(array $hashes): string
    {
        if (!$this->options['addHashes']) {
            return '';
        }

        if ($this->commitUrl !== null) {
            foreach ($hashes as &$hash) {
                $hash = "[$hash](" . str_replace('{hash}', $hash, $this->commitUrl) . ')';
            }
            unset($hash);
        }

        $hashes = implode(', ', $hashes);

        return "($hashes)";
    }
}
