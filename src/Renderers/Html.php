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
 * Class Html
 *
 * Renderer for GitChangelog.
 * The rendered changelog is formatted in markdown.
 *
 * @package DigiLive\GitChangelog\Renderers
 */
class Html extends GitChangelog implements RendererInterface
{
    /**
     * @var string Url to commit view of the remote repository. If set, hashes of commit subjects are converted into
     *             links which refer to the corresponding commit at the remote.
     *             {hash} is replaced by the issue number.
     */
    public $commitUrl;
    /**
     * @var string Url to Issue tracker of the repository. If set, issue references in commit subject are converted into
     *             links which refer to the corresponding issue at the tracker.
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
        $logContent = "<h1>{$this->options['logHeader']}</h1>";

        $commitData = $this->fetchCommitData();

        if (!$commitData) {
            $this->changelog = "$logContent<p>{$this->options['noChangesMessage']}</p>";

            return;
        }

        if (!$this->options['tagOrderDesc']) {
            $commitData = array_reverse($commitData);
        }

        foreach ($commitData as $tag => $data) {
            // Add tag header and date.
            if ($tag === '') {
                $tag          = $this->options['headTagName'];
                $data['date'] = $this->options['headTagDate'];
            }

            $logContent .= "<h2>$tag ({$data['date']})</h2><ul>";

            // No subjects present for this tag.
            if (!$data['subjects']) {
                $logContent .= "<li>{$this->options['noChangesMessage']}</li></ul>";
                continue;
            }

            // Sort commit subjects.
            Utilities::natSort($data['subjects'], $this->options['subjectOrder']);

            // Add commit subjects.
            foreach ($data['subjects'] as $subjectKey => $subject) {
                if ($this->issueUrl !== null) {
                    $subject = preg_replace(
                        '/#([0-9]+)/',
                        '<a href="' . str_replace('{issue}', '$1', $this->issueUrl) . '">$0</a>',
                        $subject
                    );
                }
                $logContent .= "<li>$subject " . $this->formatHashes($data['hashes'][$subjectKey]) . '</li>';
            }

            $logContent .= '</ul>';
        }

        $this->changelog = $logContent;
    }

    /**
     * Format the hashes of a commit subject into a string.
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

        if ($this->commitUrl !== null) {
            foreach ($hashes as &$hash) {
                $hash = '<a href="' . str_replace('{hash}', $hash, $this->commitUrl) . "\">$hash</a>";
            }
            unset($hash);
        }

        return '(' . implode(', ', $hashes) . ')';
    }
}
